<?php

namespace App\Model\Base;

use \Exception;
use \PDO;
use App\Model\Answer as ChildAnswer;
use App\Model\AnswerQuery as ChildAnswerQuery;
use App\Model\Image as ChildImage;
use App\Model\ImageQuery as ChildImageQuery;
use App\Model\Question as ChildQuestion;
use App\Model\QuestionQuery as ChildQuestionQuery;
use App\Model\Vote as ChildVote;
use App\Model\VoteQuery as ChildVoteQuery;
use App\Model\Map\AnswerTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\LogicException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;

/**
 * Base class that represents a row from the 'answer' table.
 *
 *
 *
* @package    propel.generator.App.Model.Base
*/
abstract class Answer implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\App\\Model\\Map\\AnswerTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     *
     * @var        int
     */
    protected $id;

    /**
     * The value for the question_id field.
     *
     * @var        int
     */
    protected $question_id;

    /**
     * The value for the image_id field.
     *
     * @var        int
     */
    protected $image_id;

    /**
     * The value for the vote_count field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $vote_count;

    /**
     * The value for the complaint_count field.
     *
     * Note: this column has a database default value of: 0
     * @var        int
     */
    protected $complaint_count;

    /**
     * The value for the weight field.
     *
     * @var        int
     */
    protected $weight;

    /**
     * @var        ChildQuestion
     */
    protected $aQuestion;

    /**
     * @var        ChildImage
     */
    protected $aImage;

    /**
     * @var        ObjectCollection|ChildVote[] Collection to store aggregation of ChildVote objects.
     */
    protected $collVotes;
    protected $collVotesPartial;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection|ChildVote[]
     */
    protected $votesScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->vote_count = 0;
        $this->complaint_count = 0;
    }

    /**
     * Initializes internal state of App\Model\Base\Answer object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>Answer</code> instance.  If
     * <code>obj</code> is an instance of <code>Answer</code>, delegates to
     * <code>equals(Answer)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        if (!$obj instanceof static) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey() || null === $obj->getPrimaryKey()) {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return $this|Answer The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        $cls = new \ReflectionClass($this);
        $propertyNames = [];
        foreach($cls->getProperties() as $property) {
            $propertyNames[] = $property->getName();
        }
        return $propertyNames;
    }

    /**
     * Get the [id] column value.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the [question_id] column value.
     *
     * @return int
     */
    public function getQuestionId()
    {
        return $this->question_id;
    }

    /**
     * Get the [image_id] column value.
     *
     * @return int
     */
    public function getImageId()
    {
        return $this->image_id;
    }

    /**
     * Get the [vote_count] column value.
     *
     * @return int
     */
    public function getVoteCount()
    {
        return $this->vote_count;
    }

    /**
     * Get the [complaint_count] column value.
     *
     * @return int
     */
    public function getComplaintCount()
    {
        return $this->complaint_count;
    }

    /**
     * Get the [weight] column value.
     *
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set the value of [id] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[AnswerTableMap::COL_ID] = true;
        }

        return $this;
    } // setId()

    /**
     * Set the value of [question_id] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setQuestionId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->question_id !== $v) {
            $this->question_id = $v;
            $this->modifiedColumns[AnswerTableMap::COL_QUESTION_ID] = true;
        }

        if ($this->aQuestion !== null && $this->aQuestion->getId() !== $v) {
            $this->aQuestion = null;
        }

        return $this;
    } // setQuestionId()

    /**
     * Set the value of [image_id] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setImageId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->image_id !== $v) {
            $this->image_id = $v;
            $this->modifiedColumns[AnswerTableMap::COL_IMAGE_ID] = true;
        }

        if ($this->aImage !== null && $this->aImage->getId() !== $v) {
            $this->aImage = null;
        }

        return $this;
    } // setImageId()

    /**
     * Set the value of [vote_count] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setVoteCount($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->vote_count !== $v) {
            $this->vote_count = $v;
            $this->modifiedColumns[AnswerTableMap::COL_VOTE_COUNT] = true;
        }

        return $this;
    } // setVoteCount()

    /**
     * Set the value of [complaint_count] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setComplaintCount($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->complaint_count !== $v) {
            $this->complaint_count = $v;
            $this->modifiedColumns[AnswerTableMap::COL_COMPLAINT_COUNT] = true;
        }

        return $this;
    } // setComplaintCount()

    /**
     * Set the value of [weight] column.
     *
     * @param int $v new value
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function setWeight($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->weight !== $v) {
            $this->weight = $v;
            $this->modifiedColumns[AnswerTableMap::COL_WEIGHT] = true;
        }

        return $this;
    } // setWeight()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->vote_count !== 0) {
                return false;
            }

            if ($this->complaint_count !== 0) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {

            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : AnswerTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : AnswerTableMap::translateFieldName('QuestionId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->question_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : AnswerTableMap::translateFieldName('ImageId', TableMap::TYPE_PHPNAME, $indexType)];
            $this->image_id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : AnswerTableMap::translateFieldName('VoteCount', TableMap::TYPE_PHPNAME, $indexType)];
            $this->vote_count = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : AnswerTableMap::translateFieldName('ComplaintCount', TableMap::TYPE_PHPNAME, $indexType)];
            $this->complaint_count = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : AnswerTableMap::translateFieldName('Weight', TableMap::TYPE_PHPNAME, $indexType)];
            $this->weight = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = AnswerTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException(sprintf('Error populating %s object', '\\App\\Model\\Answer'), 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aQuestion !== null && $this->question_id !== $this->aQuestion->getId()) {
            $this->aQuestion = null;
        }
        if ($this->aImage !== null && $this->image_id !== $this->aImage->getId()) {
            $this->aImage = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(AnswerTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildAnswerQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aQuestion = null;
            $this->aImage = null;
            $this->collVotes = null;

        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see Answer::setDeleted()
     * @see Answer::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(AnswerTableMap::DATABASE_NAME);
        }

        $con->transaction(function () use ($con) {
            $deleteQuery = ChildAnswerQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $this->setDeleted(true);
            }
        });
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(AnswerTableMap::DATABASE_NAME);
        }

        return $con->transaction(function () use ($con) {
            $isInsert = $this->isNew();
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                AnswerTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }

            return $affectedRows;
        });
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aQuestion !== null) {
                if ($this->aQuestion->isModified() || $this->aQuestion->isNew()) {
                    $affectedRows += $this->aQuestion->save($con);
                }
                $this->setQuestion($this->aQuestion);
            }

            if ($this->aImage !== null) {
                if ($this->aImage->isModified() || $this->aImage->isNew()) {
                    $affectedRows += $this->aImage->save($con);
                }
                $this->setImage($this->aImage);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                    $affectedRows += 1;
                } else {
                    $affectedRows += $this->doUpdate($con);
                }
                $this->resetModified();
            }

            if ($this->votesScheduledForDeletion !== null) {
                if (!$this->votesScheduledForDeletion->isEmpty()) {
                    \App\Model\VoteQuery::create()
                        ->filterByPrimaryKeys($this->votesScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->votesScheduledForDeletion = null;
                }
            }

            if ($this->collVotes !== null) {
                foreach ($this->collVotes as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[AnswerTableMap::COL_ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . AnswerTableMap::COL_ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(AnswerTableMap::COL_ID)) {
            $modifiedColumns[':p' . $index++]  = 'id';
        }
        if ($this->isColumnModified(AnswerTableMap::COL_QUESTION_ID)) {
            $modifiedColumns[':p' . $index++]  = 'question_id';
        }
        if ($this->isColumnModified(AnswerTableMap::COL_IMAGE_ID)) {
            $modifiedColumns[':p' . $index++]  = 'image_id';
        }
        if ($this->isColumnModified(AnswerTableMap::COL_VOTE_COUNT)) {
            $modifiedColumns[':p' . $index++]  = 'vote_count';
        }
        if ($this->isColumnModified(AnswerTableMap::COL_COMPLAINT_COUNT)) {
            $modifiedColumns[':p' . $index++]  = 'complaint_count';
        }
        if ($this->isColumnModified(AnswerTableMap::COL_WEIGHT)) {
            $modifiedColumns[':p' . $index++]  = 'weight';
        }

        $sql = sprintf(
            'INSERT INTO answer (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'id':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'question_id':
                        $stmt->bindValue($identifier, $this->question_id, PDO::PARAM_INT);
                        break;
                    case 'image_id':
                        $stmt->bindValue($identifier, $this->image_id, PDO::PARAM_INT);
                        break;
                    case 'vote_count':
                        $stmt->bindValue($identifier, $this->vote_count, PDO::PARAM_INT);
                        break;
                    case 'complaint_count':
                        $stmt->bindValue($identifier, $this->complaint_count, PDO::PARAM_INT);
                        break;
                    case 'weight':
                        $stmt->bindValue($identifier, $this->weight, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = AnswerTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getQuestionId();
                break;
            case 2:
                return $this->getImageId();
                break;
            case 3:
                return $this->getVoteCount();
                break;
            case 4:
                return $this->getComplaintCount();
                break;
            case 5:
                return $this->getWeight();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {

        if (isset($alreadyDumpedObjects['Answer'][$this->hashCode()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['Answer'][$this->hashCode()] = true;
        $keys = AnswerTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getQuestionId(),
            $keys[2] => $this->getImageId(),
            $keys[3] => $this->getVoteCount(),
            $keys[4] => $this->getComplaintCount(),
            $keys[5] => $this->getWeight(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aQuestion) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'question';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'question';
                        break;
                    default:
                        $key = 'Question';
                }

                $result[$key] = $this->aQuestion->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aImage) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'image';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'image';
                        break;
                    default:
                        $key = 'Image';
                }

                $result[$key] = $this->aImage->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->collVotes) {

                switch ($keyType) {
                    case TableMap::TYPE_CAMELNAME:
                        $key = 'votes';
                        break;
                    case TableMap::TYPE_FIELDNAME:
                        $key = 'votes';
                        break;
                    default:
                        $key = 'Votes';
                }

                $result[$key] = $this->collVotes->toArray(null, false, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param  string $name
     * @param  mixed  $value field value
     * @param  string $type The type of fieldname the $name is of:
     *                one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME
     *                TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                Defaults to TableMap::TYPE_PHPNAME.
     * @return $this|\App\Model\Answer
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = AnswerTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param  int $pos position in xml schema
     * @param  mixed $value field value
     * @return $this|\App\Model\Answer
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setQuestionId($value);
                break;
            case 2:
                $this->setImageId($value);
                break;
            case 3:
                $this->setVoteCount($value);
                break;
            case 4:
                $this->setComplaintCount($value);
                break;
            case 5:
                $this->setWeight($value);
                break;
        } // switch()

        return $this;
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = AnswerTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) {
            $this->setId($arr[$keys[0]]);
        }
        if (array_key_exists($keys[1], $arr)) {
            $this->setQuestionId($arr[$keys[1]]);
        }
        if (array_key_exists($keys[2], $arr)) {
            $this->setImageId($arr[$keys[2]]);
        }
        if (array_key_exists($keys[3], $arr)) {
            $this->setVoteCount($arr[$keys[3]]);
        }
        if (array_key_exists($keys[4], $arr)) {
            $this->setComplaintCount($arr[$keys[4]]);
        }
        if (array_key_exists($keys[5], $arr)) {
            $this->setWeight($arr[$keys[5]]);
        }
    }

     /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_CAMELNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     * @param string $keyType The type of keys the array uses.
     *
     * @return $this|\App\Model\Answer The current object, for fluid interface
     */
    public function importFrom($parser, $data, $keyType = TableMap::TYPE_PHPNAME)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), $keyType);

        return $this;
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(AnswerTableMap::DATABASE_NAME);

        if ($this->isColumnModified(AnswerTableMap::COL_ID)) {
            $criteria->add(AnswerTableMap::COL_ID, $this->id);
        }
        if ($this->isColumnModified(AnswerTableMap::COL_QUESTION_ID)) {
            $criteria->add(AnswerTableMap::COL_QUESTION_ID, $this->question_id);
        }
        if ($this->isColumnModified(AnswerTableMap::COL_IMAGE_ID)) {
            $criteria->add(AnswerTableMap::COL_IMAGE_ID, $this->image_id);
        }
        if ($this->isColumnModified(AnswerTableMap::COL_VOTE_COUNT)) {
            $criteria->add(AnswerTableMap::COL_VOTE_COUNT, $this->vote_count);
        }
        if ($this->isColumnModified(AnswerTableMap::COL_COMPLAINT_COUNT)) {
            $criteria->add(AnswerTableMap::COL_COMPLAINT_COUNT, $this->complaint_count);
        }
        if ($this->isColumnModified(AnswerTableMap::COL_WEIGHT)) {
            $criteria->add(AnswerTableMap::COL_WEIGHT, $this->weight);
        }

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @throws LogicException if no primary key is defined
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = ChildAnswerQuery::create();
        $criteria->add(AnswerTableMap::COL_ID, $this->id);

        return $criteria;
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        $validPk = null !== $this->getId();

        $validPrimaryKeyFKs = 0;
        $primaryKeyFKs = [];

        if ($validPk) {
            return crc32(json_encode($this->getPrimaryKey(), JSON_UNESCAPED_UNICODE));
        } elseif ($validPrimaryKeyFKs) {
            return crc32(json_encode($primaryKeyFKs, JSON_UNESCAPED_UNICODE));
        }

        return spl_object_hash($this);
    }

    /**
     * Returns the primary key for this object (row).
     * @return int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {
        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \App\Model\Answer (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setQuestionId($this->getQuestionId());
        $copyObj->setImageId($this->getImageId());
        $copyObj->setVoteCount($this->getVoteCount());
        $copyObj->setComplaintCount($this->getComplaintCount());
        $copyObj->setWeight($this->getWeight());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getVotes() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addVote($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param  boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return \App\Model\Answer Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildQuestion object.
     *
     * @param  ChildQuestion $v
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     * @throws PropelException
     */
    public function setQuestion(ChildQuestion $v = null)
    {
        if ($v === null) {
            $this->setQuestionId(NULL);
        } else {
            $this->setQuestionId($v->getId());
        }

        $this->aQuestion = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildQuestion object, it will not be re-added.
        if ($v !== null) {
            $v->addAnswer($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildQuestion object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildQuestion The associated ChildQuestion object.
     * @throws PropelException
     */
    public function getQuestion(ConnectionInterface $con = null)
    {
        if ($this->aQuestion === null && ($this->question_id !== null)) {
            $this->aQuestion = ChildQuestionQuery::create()->findPk($this->question_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aQuestion->addAnswers($this);
             */
        }

        return $this->aQuestion;
    }

    /**
     * Declares an association between this object and a ChildImage object.
     *
     * @param  ChildImage $v
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     * @throws PropelException
     */
    public function setImage(ChildImage $v = null)
    {
        if ($v === null) {
            $this->setImageId(NULL);
        } else {
            $this->setImageId($v->getId());
        }

        $this->aImage = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildImage object, it will not be re-added.
        if ($v !== null) {
            $v->addAnswer($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildImage object
     *
     * @param  ConnectionInterface $con Optional Connection object.
     * @return ChildImage The associated ChildImage object.
     * @throws PropelException
     */
    public function getImage(ConnectionInterface $con = null)
    {
        if ($this->aImage === null && ($this->image_id !== null)) {
            $this->aImage = ChildImageQuery::create()->findPk($this->image_id, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aImage->addAnswers($this);
             */
        }

        return $this->aImage;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('Vote' == $relationName) {
            return $this->initVotes();
        }
    }

    /**
     * Clears out the collVotes collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addVotes()
     */
    public function clearVotes()
    {
        $this->collVotes = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collVotes collection loaded partially.
     */
    public function resetPartialVotes($v = true)
    {
        $this->collVotesPartial = $v;
    }

    /**
     * Initializes the collVotes collection.
     *
     * By default this just sets the collVotes collection to an empty array (like clearcollVotes());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initVotes($overrideExisting = true)
    {
        if (null !== $this->collVotes && !$overrideExisting) {
            return;
        }
        $this->collVotes = new ObjectCollection();
        $this->collVotes->setModel('\App\Model\Vote');
    }

    /**
     * Gets an array of ChildVote objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildAnswer is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return ObjectCollection|ChildVote[] List of ChildVote objects
     * @throws PropelException
     */
    public function getVotes(Criteria $criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collVotesPartial && !$this->isNew();
        if (null === $this->collVotes || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collVotes) {
                // return empty collection
                $this->initVotes();
            } else {
                $collVotes = ChildVoteQuery::create(null, $criteria)
                    ->filterByAnswer($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collVotesPartial && count($collVotes)) {
                        $this->initVotes(false);

                        foreach ($collVotes as $obj) {
                            if (false == $this->collVotes->contains($obj)) {
                                $this->collVotes->append($obj);
                            }
                        }

                        $this->collVotesPartial = true;
                    }

                    return $collVotes;
                }

                if ($partial && $this->collVotes) {
                    foreach ($this->collVotes as $obj) {
                        if ($obj->isNew()) {
                            $collVotes[] = $obj;
                        }
                    }
                }

                $this->collVotes = $collVotes;
                $this->collVotesPartial = false;
            }
        }

        return $this->collVotes;
    }

    /**
     * Sets a collection of ChildVote objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $votes A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return $this|ChildAnswer The current object (for fluent API support)
     */
    public function setVotes(Collection $votes, ConnectionInterface $con = null)
    {
        /** @var ChildVote[] $votesToDelete */
        $votesToDelete = $this->getVotes(new Criteria(), $con)->diff($votes);


        $this->votesScheduledForDeletion = $votesToDelete;

        foreach ($votesToDelete as $voteRemoved) {
            $voteRemoved->setAnswer(null);
        }

        $this->collVotes = null;
        foreach ($votes as $vote) {
            $this->addVote($vote);
        }

        $this->collVotes = $votes;
        $this->collVotesPartial = false;

        return $this;
    }

    /**
     * Returns the number of related Vote objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related Vote objects.
     * @throws PropelException
     */
    public function countVotes(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collVotesPartial && !$this->isNew();
        if (null === $this->collVotes || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collVotes) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getVotes());
            }

            $query = ChildVoteQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByAnswer($this)
                ->count($con);
        }

        return count($this->collVotes);
    }

    /**
     * Method called to associate a ChildVote object to this object
     * through the ChildVote foreign key attribute.
     *
     * @param  ChildVote $l ChildVote
     * @return $this|\App\Model\Answer The current object (for fluent API support)
     */
    public function addVote(ChildVote $l)
    {
        if ($this->collVotes === null) {
            $this->initVotes();
            $this->collVotesPartial = true;
        }

        if (!$this->collVotes->contains($l)) {
            $this->doAddVote($l);
        }

        return $this;
    }

    /**
     * @param ChildVote $vote The ChildVote object to add.
     */
    protected function doAddVote(ChildVote $vote)
    {
        $this->collVotes[]= $vote;
        $vote->setAnswer($this);
    }

    /**
     * @param  ChildVote $vote The ChildVote object to remove.
     * @return $this|ChildAnswer The current object (for fluent API support)
     */
    public function removeVote(ChildVote $vote)
    {
        if ($this->getVotes()->contains($vote)) {
            $pos = $this->collVotes->search($vote);
            $this->collVotes->remove($pos);
            if (null === $this->votesScheduledForDeletion) {
                $this->votesScheduledForDeletion = clone $this->collVotes;
                $this->votesScheduledForDeletion->clear();
            }
            $this->votesScheduledForDeletion[]= clone $vote;
            $vote->setAnswer(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this Answer is new, it will return
     * an empty collection; or if this Answer has previously
     * been saved, it will retrieve related Votes from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in Answer.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return ObjectCollection|ChildVote[] List of ChildVote objects
     */
    public function getVotesJoinUser(Criteria $criteria = null, ConnectionInterface $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildVoteQuery::create(null, $criteria);
        $query->joinWith('User', $joinBehavior);

        return $this->getVotes($query, $con);
    }

    /**
     * Clears the current object, sets all attributes to their default values and removes
     * outgoing references as well as back-references (from other objects to this one. Results probably in a database
     * change of those foreign objects when you call `save` there).
     */
    public function clear()
    {
        if (null !== $this->aQuestion) {
            $this->aQuestion->removeAnswer($this);
        }
        if (null !== $this->aImage) {
            $this->aImage->removeAnswer($this);
        }
        $this->id = null;
        $this->question_id = null;
        $this->image_id = null;
        $this->vote_count = null;
        $this->complaint_count = null;
        $this->weight = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references and back-references to other model objects or collections of model objects.
     *
     * This method is used to reset all php object references (not the actual reference in the database).
     * Necessary for object serialisation.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collVotes) {
                foreach ($this->collVotes as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        $this->collVotes = null;
        $this->aQuestion = null;
        $this->aImage = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(AnswerTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
