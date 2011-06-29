<?php
/**
 * Appfuel
 * PHP 5.3+ object oriented MVC framework supporting domain driven design. 
 *
 * @package     Appfuel
 * @author      Robert Scott-Buccleuch <rsb.code@gmail.com.com>
 * @copyright   2009-2010 Robert Scott-Buccleuch <rsb.code@gmail.com>
 * @license		http://www.apache.org/licenses/LICENSE-2.0
 */
namespace Appfuel\Db\Mysql\Adapter;

use mysqli_stmt,
	mysqli_result,
	Appfuel\Framework\Exception;

/**
 * Wraps the mysqli_stmt. There is some complex logic we don't want the 
 * adapter to know about
 */
class PreparedStmt
{
	/**
	 * Mysqli object used to interact with the database
	 * @var	Mysqli
	 */	
	protected $handle = null;

	/**
	 * Flag used to determine is the statement was successfully prepared
	 * @var bool
	 */
	protected $isPrepared = false;

	/**
	 * Flag used to determine that parameters have been successfully bound
	 * @var bool
	 */
	protected $isParamsBound = false;

	/**
	 * Holds a reference to the bound parameters
	 * @var array
	 */
	protected $boundParams = array();

	/**
	 * Flag used to determine if the statement has been executed
	 * @var bool
	 */
	protected $isExecuted = false;

	/**
	 * Flag used to determine if the executed query will return a resultset.
	 * Some queries like update, delete, insert do not produce resultsets.
	 * @var bool
	 */
	protected $isResultset = false;

	/**
	 * Flag used to determine if the resultset was bound with 
	 * mysqli_stmt::bind_results
	 * @var bool
	 */
	protected $isBoundResultset = false;

	/**
	 * Flag used to determin if the resultset was actually fetched
	 * @var	bool
	 */
	protected $isFeteched = false;

	/**
	 * Flag used to determine that the handle was explicitly closed
	 * @var bool
	 */
	protected $isClosed = false;

	/**
	 * Flag used to determine that an error of some kind as occured
	 * @var bool
	 */
	protected $isError = false;

	/**
	 * Flag used to determine if the resultset used is buffered
	 * @var bool
	 */
	protected $isBufferedResultset = false;

	/**
	 * Error object that holds the error code and message
	 * @var Error
	 */
	protected $error = null;

	/**
	 * Used to hold the results of the the prepared statement
	 * @var array
	 */
	protected $columnData = array(
		'names'  => array(),
		'values' => array()
	);
	
	/**
	 * @param	ConnectionDetail	$detail
	 * @return	Adapter
	 */
	public function __construct(mysqli_stmt $handle)
	{
		$this->handle = $handle;
	}

	/**
	 * @return	Mysqli
	 */
	public function getHandle()
	{
		return $this->handle;
	}

	/**
	 * @return	bool
	 */
	public function isHandle()
	{
		return $this->handle instanceof mysqli_stmt;
	}

	/**
	 * @return bool
	 */
	public function isClosed()
	{
		return $this->isClosed;
	}

	/**
	 * Explictly close the handle. Can not close the handle (mysqli_stmt) 
	 * before it has been prepared.
	 *
	 * @throws	Appfuel\Framework\Exeception	when handle is already closed
	 * @throws	\Exception	when trying to close before prepared
	 * @return	bool
	 */
	public function close()
	{
		$this->validateHandle('close');	
		$handle = $this->getHandle();
	
		try {
			$handle->close();
			$this->handle = null;
		}
		catch (\Exception $e) {
			$this->setError($e->getCode(), trim($e->getMessage()));
			$this->isClosed = false;
			$this->handle   = null;
			return false;
		}
		
		$this->isClosed = true;
		return true;
	}

	/**
	 * @return bool
	 */
	public function isError()
	{
		return $this->isError;
	}
	
	/**
	 * @return Error
	 */
	public function getError()
	{
		return $this->error;
	}

	/**
	 * @return bool
	 */
	public function isPrepared()
	{
		return $this->isPrepared;
	}

	/**
	 * Prepare the sql for execution
	 * 
	 * @param	string	$sql	sql query to be prepared
	 * @return	bool
	 */
	public function prepare($sql)
	{
		$this->validateHandle('prepare');

		if (! is_string($sql) || empty($sql)) {
			throw new Exception("sql must be a non empty string");
		}

		$hdl = $this->getHandle();
		if (! $hdl->prepare($sql)) {
			$this->setError($hdl->errno, $hdl->error, $hdl->sqlstate);
			$this->isPrepared = false;
			return false;
		}

		$this->isPrepared = true;
		return true;
	}

	/**
	 * Normalize the parameters so they can by used in the bind_params call
	 * and bind them
	 *
	 * @param	array	$params		list of values in the prepared stmt
	 * @return	bool
	 */
	public function organizeParams(array $params)
	{
		$handle = $this->getHandle();
		return $this->bindParams($this->normalizeParams($params));
	}

	/**
	 * In order to work with an unkown number of prameters generically we
	 * need to inspect the orignal parameters and normalized into an array
	 * that it compatable with call_user_func_array. The array structure:
	 * first element is a string where each char 's' represents an item 
	 * in the array. The other elements represent the orignal parameters.
	 *
	 * @param	array	$params		list of values used in prepared stmt
	 * @return	array
	 */
	public function normalizeParams(array $params) 
	{
		if (empty($params)) {
			return array();
		}

        $bindParams = array();
		/*
		 * mysqli_stmt::bind_params can handle 4 different types
		 * i: int, d: double, s: string and b: for blobs. I choose everything
		 * to be a string knowing they are casted to their correct types once
		 * handed over to mysql. Also we can handle objects that support a
		 * __toString method
		 */
        $paramType = 's';
        $bindTypes = '';
        foreach ($params as $param) {

            /*
             * If the value is an array, the IN expression for example then we
             * need an type param for each item in the array. we assume the
             * array contains all the same types
             */
            if (is_array($param)) {
                $bindTypes .= str_repeat($paramType, count($param));
                $bindParams = array_merge($bindParams,$param);
            } else {
                $bindTypes .= $paramType;
                $bindParams[] = $param;
            }
        }

        /*
         * make the first item a string of parameter types
         */
        array_unshift($bindParams, $bindTypes);
		
		if (empty($bindParams[0])) {
			return array();
		}

        return $bindParams;
	}

	/**
	 * Binds multiple parameters with one call to call_user_func_array. Each
	 * item in the array (accept the first) represents a parameter marker in
	 * sql stmt. The first parameter is a string where each character 
	 * also represents a sql parameter marker.
	 * 
	 * @param	array	$params		list of arguments for bind_params
	 * @return	bool
	 */
	public function bindParams(array $params)
	{
		$isValidCount    = count($params) >= 2;
		$isValidFirstElm = isset($params[0]) && 
						   is_string($params[0]) && 
						   ! empty($params[0]);

		if (! $isValidCount || ! $isValidFirstElm) {
			$this->isParamsBound = false;
			$this->setError(10000, 'bindParams fail: invalid args passed in');
			return false;
		}

		$this->validateHandle('bindParams');
		$handle = $this->getHandle();

		if (! $this->isPrepared()) {
			$this->isParamsBound = false;
			$this->setError(10001, 'can not execute before prepare');		
			return false;
		}

		/*
         * clear out params
         */
        $this->boundParams = array();

        /*
         * make the first item a string of parameter types
         */
        foreach ($params as $key => &$value) {
            $this->boundParams[$key] =& $value;
        }

        /*
         * Bind all the parameters and return the result
         */
        $result = call_user_func_array(
			array($handle, 'bind_param'),
            $this->boundParams
        );
		
		$this->isParamsBound = $result;
		return $result;
	}

	/**
	 * @return bool
	 */
	public function isParamsBound()
	{
		return $this->isParamsBound;
	}

	/**
	 * @return bool
	 */
	public function isExecuted()
	{
		return $this->isExecuted;
	}

	/**
	 * @param	string	$sql	
	 * @param	array	$params		values in the prepared sql
	 * @return	bool
	 */
	public function execute()
	{
		$this->validateHandle('execute');
		$hdl = $this->getHandle();
		
		if (! $this->isPrepared()) {
			$this->isExecuted = false;
			$this->setError(10001, 'can not execute before prepare');		
			return false;
		}

		if (! $hdl->execute()) {
			$this->isExecuted = false;
			$this->setError($hdl->errno, $hdl->error, $hdl->sqlstate);		
			return false;
		}

		$this->isExecuted = true;
		return true;
	}

	/**
	 * @return bool
	 */
	public function organizeResults()
	{
		$this->validateHandle('organizeResults');		
		$handle = $this->getHandle();

		if (! $this->isPrepared()) {
			$this->isResultset = false;
			$this->setError(10001, 'can not organize results before prepare');	
			return false;
		}

		$resultHandle = $handle->result_metadata();
		if (! $resultHandle instanceof mysqli_result) {
			/* with no errors then this is a statement that executed and 
			 * produced no results like an update for example
			 */
			$this->isResultSet      = false;
			$this->isBoundResultset = false;	
			return true;
		}

		$result = new Result($resultHandle);
        /*
         * Preload column values with nulls
         */
		$cnames = $result->getColumnNames();
		$this->columnData = array(
			'names'  => $cnames,
			'values' => array_fill(0, count($cnames), null)
		);
		$result->free();

        $refs = array();
        foreach ($this->columnData['values'] as $index => &$var) {
            $refs[$index] = &$var;
        }

        /*
         * Bind to the result variables. We need the actual mysqli_stmt object
         */
        $ok = call_user_func_array(
            array($handle, 'bind_result'),
            $this->columnData['values']
        );
	
		$this->isBoundResultset = true;	
		$this->isResultset = true;
		
		return true;
	}

	/**
	 * Buffer the full resultset into memory
	 *
	 * @throws	Appfuel\Framework\Exeception	when resultset is not bound
	 * @return bool
	 */
	public function storeResults()
	{
		if (! $this->isBoundResultset()) {
			$this->setError(
				10004, 
				'can not store results without bounded resultset'
			);
			return false;
		}
		$handle = $this->getHandle();


		if (! $handle->store_result()) {
			$this->setError(
				$handle->errno,
				$handle->error,
				$handle->sqlstate
			);
			$this->isBufferedResultset = false;
			return false;
		}
	
		$this->isBufferedResultset = true;
		return true;	
	}

	/**
	 * Frees the result memory associated with the statement, which was 
	 * allocated by mysqli_stmt_store_result().
	 * 
	 * @return null
	 */
	public function freeStoredResults()
	{
		if (! $this->isBoundResultset()) {
			$this->setError(10004, 'resultset not bounded: nothing to free');
			return false;
		}

		if (! $this->isBufferedResultset()) {
			$this->setError(10006, 'can only free buffered results');
			return false;
		}
	
		$this->getHandle()
			 ->free_result();


		$this->isExecuted			= false;
		$this->isBufferedResultset	= false;
		$this->isBoundResultset		= false;
		$this->isResultset			= false;	
	}


	/**
	 * @return bool
	 */
	public function isBufferedResultset()
	{
		return $this->isBufferedResultset;
	}

	/**
	 * @return	bool
	 */
	public function isResultset()
	{
		return $this->isResultset;
	}

	/**
	 * @return	bool
	 */
	public function isBoundResultset()
	{
		return $this->isBoundResultset;
	}

	/**
	 * @return array
	 */
	public function fetch()
	{		
		if (! $this->isBoundResultset()) {
			$this->setError(10004, 'can not fetch without bounded resultset');
			$this->isFeteched = false;
			return false;
		}
		$this->isFetched = true;
		return $this->doFetch();
	}

	/**
	 * @return array
	 */
	public function fetchAll()
	{
		if (! $this->isBoundResultset()) {
			$this->setError(10004, 'can not fetch without bounded resultset');
			$this->isFetched = false;
			return false;
		}

		$data = array();
		while ($row = $this->doFetch()) {
			$data[] = $row;	
		}
		$this->isFetched = true;
		return $data;
	}

	/**
	 * @return bool
	 */
	public function isFetched()
	{
		return $this->isFetched;
	}

	/**
	 * Get the ID generated from the previous INSERT operation
	 *
	 * @return	int | null
	 */
	public function getLastInsertId()
	{
		$this->validateHandle('getLastInsertId');
		return $this->getHandle()
					->insert_id;
	}

	/**
	 * Resets a prepared statement on client and server to state after prepare.
	 * It resets the statement on the server, data sent using 
	 * mysqli_stmt_send_long_data(), unbuffered result sets and current errors.
	 * It does not clear bindings or stored result sets. Stored result sets 
	 * will be cleared when executing the prepared statement (or closing it).
	 *
	 * @return bool
	 */
	public function reset()
	{
		$this->validateHandle('getLastInsertId');
		$handle = $this->getHandle();

		$isReset = $handle->reset();
		if ($isReset) {
			$this->isPrepared			= false;
			$this->isBufferedResultset	= false;
			$this->isError				= false;
			$this->error				= null;
			$this->columnData			= array(
				'names'  => array(),
				'values' => array()
			);
			return true;
		}

		$this->setError(10007, 'could not reset stmt');
		return false;
	}

	/**
	 * This is isolated so we can put it in a while loop and not worry about
	 * extra validaton or uneeded function calls. Therefore you are required
	 * to validate the correct conditions before calling this
	 *
	 * @return	array | null | false
	 */
	protected function doFetch()
	{
        switch ($this->handle->fetch()) {
            case true:
                $result = array_combine(
					$this->columnData['names'],
					$this->dereferenceColumnValues()
				);
                break;
            case null:
                $result = null;
                break;

            case false:
                $result = false;
                $this->setError(
					$handle->errno,
					$handle->error,
					$handle->sqlstate
				);
                break;

            default:
                $result = false;
				$this->setError(
					10006, 
					'unknown return value mysqli_stmt::fetch'
				);
        }

		return $result;

	}

	/**
	 * Dereference the result values, otherwise things like fetchAll()
     * return the same values for every entry (because of the reference).
	 *
	 * @return	array
     */
	protected function dereferenceColumnValues()
	{
		if (! is_array($this->columnData['values'])) {
			throw new Exception("Column values need to be in an array");
		}

		$refs   = $this->columnData['values'];
		$values = array(); 
		foreach ($refs as $idx => $value) {
			$values[] = $value;
		}
	
		return $values;
	}


    /**
     * Ensures that the handle is available and ready to use
     *
     * @return null
     */
    protected function validateHandle($method)
    {  
        if (! $this->isHandle()) {
            $err = 'Stmt handle has been closed:';
            throw new Exception("$err operation ($method) is invalid");
        }
    }

	/**
	 * @param	int		$code		mysql specific code for error
	 * @param	string	$text		mysql text for code
	 * @param	string	$sqlState	Ansi sql portable error code
	 * @return	Error
	 */
	protected function setError($code, $text = null, $sqlState = null)
	{
		$this->error   = new Error($code, $text, $sqlState);
		$this->isError = true;
	}
}