<?php
namespace WebbuildersGroup\FluentWorkflow\DataLists;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataQuery;
use SilverStripe\ORM\ManyManyList;
use SilverStripe\ORM\SS_List;
use SilverStripe\ORM\Queries\SQLDelete;
use SilverStripe\ORM\Queries\SQLSelect;
use TractorCow\Fluent\State\FluentState;

class FluentWorkflowDefinitionList extends ManyManyList
{
    protected $filterLocale;
    protected $localeFilterEnabled = true;
    
    const LOCALE_FILTER_EXPRESSION = '"%s"."JunctionLocale" = ? ';
    
    
    /**
     * Create a new FluentManyManyList object.
     * A ManyManyList object represents a list of {@link DataObject} records
     * that correspond to a many-many relationship.
     * Generation of the appropriate record set is left up to the caller, using
     * the normal {@link DataList} methods. Addition arguments are used to
     * support {@@link add()} and {@link remove()} methods.
     *
     * @param string $dataClass The class of the DataObjects that this will list.
     * @param string $joinTable The name of the table whose entries define the content of this many_many relation.
     * @param string $localKey The key in the join table that maps to the dataClass' PK.
     * @param string $foreignKey The key in the join table that maps to joined class' PK.
     * @param array $extraFields A map of field => fieldtype of extra fields on the join table.
     *
     * @example new FluentManyManyList('Group','Group_Members', 'GroupID', 'MemberID');
     */
    public function __construct($dataClass, $joinTable, $localKey, $foreignKey, $locale, $extraFields = [])
    {
        parent::__construct($dataClass, $joinTable, $localKey, $foreignKey, $extraFields);
        
        if (empty($locale)) {
            $locale = FluentState::singleton()->getLocale();
        }
        
        $this->filterLocale = $locale;
        
        $this->dataQuery->where([sprintf(self::LOCALE_FILTER_EXPRESSION, $this->joinTable) => $this->filterLocale]);
        
        if (!array_key_exists('JunctionLocale', $this->extraFields)) {
            $this->extraFields['JunctionLocale'] = 'Varchar(10)';
        }
    }
    
    /**
     * Enables or disagbles the locale filtering of the list
     * @param bool $enabled Whether or not to enable the filtering of the list by locale
     * @return FluentManyManyList Clone of the original list
     */
    public function setLocaleFilterEnabled($enabled = true)
    {
        $preSet = $this->localeFilterEnabled;
        $this->localeFilterEnabled = $enabled;
        
        $list = $this->alterDataQuery(function (DataQuery $query, SS_List $list) {
            if ($this->localeFilterEnabled) {
                $query->where([sprintf(self::LOCALE_FILTER_EXPRESSION, $this->joinTable) => $this->filterLocale]);
            } else {
                $query->removeFilterOn([sprintf(self::LOCALE_FILTER_EXPRESSION, $this->joinTable) => $this->filterLocale]);
            }
        });
        
        $this->localeFilterEnabled = $preSet;
        
        return $list;
    }
    
    /**
     * Sets the locale used in the list
     * @param string $locale Locale to use when filtering this list
     * @return FluentManyManyList Clone of the original list
     */
    public function setLocale($locale)
    {
        $preSet = $this->Locale;
        $this->filterLocale = $locale;
        
        $list = $this->alterDataQuery(function (DataQuery $query, SS_List $list) use ($preSet, $locale) {
            if ($this->localeFilterEnabled) {
                $query->removeFilterOn([sprintf(self::LOCALE_FILTER_EXPRESSION, $this->joinTable) => $preSet]);
                
                $query->where([sprintf(self::LOCALE_FILTER_EXPRESSION, $this->joinTable) => $locale]);
            }
        });
        
        $this->filterLocale = $preSet;
        
        return $list;
    }
    
    /**
     * Add an item to this many_many relationship, does so by adding an entry to the joinTable.
     * Can also be used to update an already existing joinTable entry: $manyManyList->add($recordID,["ExtraField" => "value"]);
     *
     * @param DataObject|int $item
     * @param array $extraFields A map of additional columns to insert into the joinTable. Column names should be ANSI quoted.
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function add($item, $extraFields = [])
    {
        //Ensure nulls or empty strings are correctly treated as empty arrays
        if (empty($extraFields)) {
            $extraFields = [];
        }
        
        $extraFields['JunctionLocale'] = $this->filterLocale;
        
        // Determine ID of new record
        $itemID = null;
        if (is_numeric($item)) {
            $itemID = $item;
        } else if ($item instanceof $this->dataClass) {
            // Ensure record is saved
            if (!$item->isInDB()) {
                $item->write();
            }
            
            $itemID = $item->ID;
        } else {
            throw new InvalidArgumentException("ManyManyList::add() expecting a $this->dataClass object, or ID value");
        }
        
        if (empty($itemID)) {
            throw new InvalidArgumentException("ManyManyList::add() couldn't add this record");
        }
        
        // Validate foreignID
        $foreignIDs = $this->getForeignID();
        if (empty($foreignIDs)) {
            throw new BadMethodCallException("ManyManyList::add() can't be called until a foreign ID is set");
        }
        
        // Apply this item to each given foreign ID record
        if (!is_array($foreignIDs)) {
            $foreignIDs = [$foreignIDs];
        }
        
        foreach ($foreignIDs as $foreignID) {
            // Check for existing records for this item
            if ($foreignFilter = $this->foreignIDWriteFilter($foreignID)) {
                // With the current query, simply add the foreign and local conditions
                // The query can be a bit odd, especially if custom relation classes
                // don't join expected tables (@see Member_GroupSet for example).
                $query = SQLSelect::create("*", "\"{$this->joinTable}\"");
                $query->addWhere($foreignFilter);
                $query->addWhere([
                    "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID,
                    "\"{$this->joinTable}\".\"JunctionLocale\"" => $this->filterLocale,
                ]);
                $hasExisting = ($query->count() > 0);
            } else {
                $hasExisting = false;
            }
            
            // Blank manipulation
            $manipulation = [
                $this->joinTable => [
                    'command' => ($hasExisting ? 'update' : 'insert'),
                    'fields' => [],
                ],
            ];
            
            if ($hasExisting) {
                $manipulation[$this->joinTable]['where'] = [
                    "\"{$this->joinTable}\".\"{$this->foreignKey}\"" => $foreignID,
                    "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID,
                    "\"{$this->joinTable}\".\"JunctionLocale\"" => $this->filterLocale,
                ];
            }
            
            if ($extraFields && $this->extraFields) {
                // Write extra field to manipluation in the same way
                // that DataObject::prepareManipulationTable writes fields
                foreach ($this->extraFields as $fieldName => $fieldSpec) {
                    // Skip fields without an assignment
                    if (array_key_exists($fieldName, $extraFields)) {
                        $fieldObject = Injector::inst()->create($fieldSpec, $fieldName);
                        $fieldObject->setValue($extraFields[$fieldName]);
                        $fieldObject->writeToManipulation($manipulation[$this->joinTable]);
                    }
                }
            }
            
            $manipulation[$this->joinTable]['fields'][$this->localKey] = $itemID;
            $manipulation[$this->joinTable]['fields'][$this->foreignKey] = $foreignID;
            
            // Make sure none of our field assignments are arrays
            foreach ($manipulation as $tableManipulation) {
                if (!isset($tableManipulation['fields'])) {
                    continue;
                }
                
                foreach ($tableManipulation['fields'] as $fieldValue) {
                    if (is_array($fieldValue)) {
                        throw new InvalidArgumentException('ManyManyList::add: parameterised field assignments are disallowed');
                    }
                }
            }
            
            DB::manipulate($manipulation);
        }
    }
    
    /**
     * Remove the given item from this list. Note that for a ManyManyList, the item is never actually deleted, only the join table is affected
     * @param int $itemID The item ID
     */
    public function removeByID($itemID)
    {
        if (!is_numeric($itemID)) {
            throw new InvalidArgumentException("ManyManyList::removeById() expecting an ID");
        }
        
        $query = SQLDelete::create("\"{$this->joinTable}\"");
        
        if ($filter = $this->foreignIDWriteFilter($this->getForeignID())) {
            $query->setWhere($filter);
        } else {
            user_error("Can't call ManyManyList::remove() until a foreign ID is set");
        }
        
        $query->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\"" => $itemID,
            "\"{$this->joinTable}\".\"JunctionLocale\"" => $this->filterLocale,
        ]);
        $query->execute();
    }
    
    /**
     * Remove all items from this many-many join.  To remove a subset of items, filter it first.
     * @return void
     */
    public function removeAll()
    {
        // Remove the join to the join table to avoid MySQL row locking issues.
        $query = $this->dataQuery();
        $foreignFilter = $query->getQueryParam('Foreign.Filter');
        $query->removeFilterOn($foreignFilter);
        
        // Select ID column
        $selectQuery = $query->query();
        $dataClassIDColumn = DataObject::getSchema()->sqlColumnForField($this->dataClass(), 'ID');
        $selectQuery->setSelect($dataClassIDColumn);
        
        $from = $selectQuery->getFrom();
        unset($from[$this->joinTable]);
        $selectQuery->setFrom($from);
        $selectQuery->setOrderBy(); // ORDER BY in subselects breaks MS SQL Server and is not necessary here
        $selectQuery->setDistinct(false);
        
        // Use a sub-query as SQLite does not support setting delete targets in
        // joined queries.
        $delete = SQLDelete::create();
        $delete->setFrom("\"{$this->joinTable}\"");
        $delete->addWhere($this->foreignIDFilter());
        $delete->addWhere([
            "\"{$this->joinTable}\".\"JunctionLocale\"" => $this->filterLocale,
        ]);
        $parameters = [];
        $subSelect = $selectQuery->sql($parameters);
        $delete->addWhere([
            "\"{$this->joinTable}\".\"{$this->localKey}\" IN ($subSelect)" => $parameters,
        ]);
        $delete->execute();
    }
}
