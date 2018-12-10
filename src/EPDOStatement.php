<?php
/**
 * Copyright 2015 github.com/noahheck
 * Copyright 2017 - 2018 github.com/xsuchy09
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/*
 * Notice - can not use param type definition in override methods - Warning: Declaration of EPDOStatement should be compatible with PDOStatement...
 */

namespace EPDOStatement;

use \PDO as PDO;
use \PDOStatement as PDOStatement;

/**
 * Class EPDOStatement
 * @package EPDOStatement
 */
class EPDOStatement extends PDOStatement
{

	/**
	 * The first argument passed in should be an instance of the PDO object. If so, we'll cache it's reference locally
	 * to allow for the best escaping possible later when interpolating our query. Other parameters can be added if
	 * needed.
	 *
	 * @param PDO|null $pdo
	 */
	protected function __construct(?PDO $pdo = null)
	{
		if ($pdo !== null) {
			$this->_pdo = $pdo;
		}
	}

	/**
	 * @var PDO $_pdo
	 */
	protected $_pdo;

	/**
	 * @var string $fullQuery - will be populated with the interpolated db query string
	 */
	public $fullQuery;

	/**
	 * @var array $boundParams - array of arrays containing values that have been bound to the query as parameters
	 */
	protected $boundParams = [];

	/**
	 * Overrides the default \PDOStatement method to add the named parameter and it's reference to the array of bound
	 * parameters - then accesses and returns parent::bindParam method
	 *
	 * @param mixed    $param
	 * @param mixed    $value
	 * @param int|null $datatype
	 * @param int      $length
	 * @param mixed    $driverOptions
	 *
	 * @return bool - default of \PDOStatement::bindParam()
	 */
	public function bindParam($param, &$value, $datatype = null, $length = null, $driverOptions = null): bool
	{
		/*if ($datatype === null) {
			$datatype = PDO::PARAM_STR;
		}*/
		$this->boundParams[$param] = [
			'value' => &$value,
			'datatype' => $datatype
		];

		return parent::bindParam($param, $value, $datatype, $length, $driverOptions);
	}

	/**
	 * Overrides the default \PDOStatement method to add the named parameter and it's value to the array of bound values
	 * - then accesses and returns parent::bindValue method
	 *
	 * @param mixed    $param
	 * @param mixed    $value
	 * @param int|null $datatype
	 *
	 * @return bool - default of \PDOStatement::bindValue()
	 */
	public function bindValue($param, $value, $datatype = null): bool
	{
		$this->boundParams[$param] = [
			'value' => $value,
			'datatype' => $datatype
		];

		return parent::bindValue($param, $value, $datatype);
	}

	/**
	 * Copies $this->queryString then replaces bound markers with associated values ($this->queryString is not modified
	 * but the resulting query string is assigned to $this->fullQuery)
	 *
	 * @param array|null $inputParams - array of values to replace ? marked parameters in the query string
	 *
	 * @return string $testQuery - interpolated db query string
	 */
	public function interpolateQuery(?array $inputParams = null): string
	{
		$testQuery = $this->queryString;

		$params = (true === isset($this->boundParams) ? $this->boundParams : $inputParams);

		if ($params) {

			ksort($params);

			foreach ($params as $key => $value) {

				$replValue = (true === is_array($value) ? $value : [
					'value' => $value,
					'datatype' => PDO::PARAM_STR
				]);

				$replValue = $this->prepareValue($replValue);

				$testQuery = $this->replaceMarker($testQuery, $key, $replValue);
			}
		}

		$this->fullQuery = $testQuery;

		return $testQuery;
	}

	/**
	 * @param string              $queryString
	 * @param string|int          $marker
	 * @param string|string[]|int $replValue
	 *
	 * @return string|string[]|null
	 */
	private function replaceMarker(string $queryString, $marker, $replValue)
	{
		/**
		 * UPDATE - Issue #3
		 * It is acceptable for bound parameters to be provided without the leading :, so if we are not matching
		 * a ?, we want to check for the presence of the leading : and add it if it is not there.
		 */
		if (is_numeric($marker)) {
			$marker = '\?';
			$limit = 1;
		} else {
			$marker = (preg_match('/^:/', $marker)) ? $marker : ':' . $marker;
			$limit = -1;
		}

		//$testParam = '/({$marker}(?!\w))(?=(?:[^"\']|["\'][^"\']*["\'])*$)/';
		$testParam = '/(' . $marker . '(?!\w))/';

		return preg_replace($testParam, $replValue, $queryString, $limit);
	}

	/**
	 * Overrides the default \PDOStatement method to generate the full query string - then accesses and returns
	 * parent::execute method
	 *
	 * @param array|null $inputParams
	 *
	 * @return bool - default of \PDOStatement::execute()
	 */
	public function execute($inputParams = null): bool
	{
		$this->interpolateQuery($inputParams);

		return parent::execute($inputParams);
	}

	/**
	 * Prepares values for insertion into the resultant query string - if $this->_pdo is a valid PDO object, we'll use
	 * that PDO driver's quote method to prepare the query value. Otherwise:
	 *
	 *      addslashes is not suitable for production logging, etc. You can update this method to perform the necessary
	 *      escaping translations for your database driver. Please consider updating your processes to provide a valid
	 *      PDO object that can perform the necessary translations and can be updated with your e.g. package management,
	 *      etc.
	 *
	 * @param array $value - the value to be prepared for injection as a value in the query string
	 *
	 * @return string|int $value - prepared $value
	 */
	private function prepareValue(array $value)
	{
		if ($value['value'] === null) {
			return 'NULL';
		}

		if ($this->_pdo instanceof PDO === false) {
			return '\'' . addslashes($value['value']) . '\'';
		}

		if (PDO::PARAM_INT === $value['datatype']) {
			return (int)$value['value'];
		}

		return $this->_pdo->quote($value['value']);
	}

}
