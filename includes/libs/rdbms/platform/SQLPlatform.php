<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */
namespace Wikimedia\Rdbms\Platform;

use InvalidArgumentException;
use Wikimedia\Rdbms\Database\DbQuoter;
use Wikimedia\Rdbms\DBLanguageError;

/**
 * Sql abstraction object.
 * This class nor any of its subclasses shouldn't create a db connection.
 * It also should not become stateful. The constructor should only rely on addQuotes() method in Database.
 * Later that should be replaced with an implementation that doesn't use db connections.
 * @since 1.39
 */
class SQLPlatform implements ISQLPlatform {
	/** @var DbQuoter */
	protected $quoter;

	public function __construct( DbQuoter $quoter ) {
		$this->quoter = $quoter;
	}

	/**
	 * @inheritDoc
	 * @stable to override
	 */
	public function bitNot( $field ) {
		return "(~$field)";
	}

	/**
	 * @inheritDoc
	 * @stable to override
	 */
	public function bitAnd( $fieldLeft, $fieldRight ) {
		return "($fieldLeft & $fieldRight)";
	}

	/**
	 * @inheritDoc
	 * @stable to override
	 */
	public function bitOr( $fieldLeft, $fieldRight ) {
		return "($fieldLeft | $fieldRight)";
	}

	/**
	 * @inheritDoc
	 * @stable to override
	 */
	public function addIdentifierQuotes( $s ) {
		return '"' . str_replace( '"', '""', $s ) . '"';
	}

	/**
	 * @inheritDoc
	 */
	public function buildGreatest( $fields, $values ) {
		return $this->buildSuperlative( 'GREATEST', $fields, $values );
	}

	/**
	 * @inheritDoc
	 */
	public function buildLeast( $fields, $values ) {
		return $this->buildSuperlative( 'LEAST', $fields, $values );
	}

	/**
	 * Build a superlative function statement comparing columns/values
	 *
	 * Integer and float values in $values will not be quoted
	 *
	 * If $fields is an array, then each value with a string key is treated as an expression
	 * (which must be manually quoted); such string keys do not appear in the SQL and are only
	 * descriptive aliases.
	 *
	 * @param string $sqlfunc Name of a SQL function
	 * @param string|string[] $fields Name(s) of column(s) with values to compare
	 * @param string|int|float|string[]|int[]|float[] $values Values to compare
	 * @return string
	 */
	protected function buildSuperlative( $sqlfunc, $fields, $values ) {
		$fields = is_array( $fields ) ? $fields : [ $fields ];
		$values = is_array( $values ) ? $values : [ $values ];

		$encValues = [];
		foreach ( $fields as $alias => $field ) {
			if ( is_int( $alias ) ) {
				$encValues[] = $this->addIdentifierQuotes( $field );
			} else {
				$encValues[] = $field; // expression
			}
		}
		foreach ( $values as $value ) {
			if ( is_int( $value ) || is_float( $value ) ) {
				$encValues[] = $value;
			} elseif ( is_string( $value ) ) {
				$encValues[] = $this->quoter->addQuotes( $value );
			} elseif ( $value === null ) {
				throw new DBLanguageError( 'Null value in superlative' );
			} else {
				throw new DBLanguageError( 'Unexpected value type in superlative' );
			}
		}

		return $sqlfunc . '(' . implode( ',', $encValues ) . ')';
	}

	public function makeList( array $a, $mode = self::LIST_COMMA ) {
		$first = true;
		$list = '';

		foreach ( $a as $field => $value ) {
			if ( $first ) {
				$first = false;
			} else {
				if ( $mode == self::LIST_AND ) {
					$list .= ' AND ';
				} elseif ( $mode == self::LIST_OR ) {
					$list .= ' OR ';
				} else {
					$list .= ',';
				}
			}

			if ( ( $mode == self::LIST_AND || $mode == self::LIST_OR ) && is_numeric( $field ) ) {
				$list .= "($value)";
			} elseif ( $mode == self::LIST_SET && is_numeric( $field ) ) {
				$list .= "$value";
			} elseif (
				( $mode == self::LIST_AND || $mode == self::LIST_OR ) && is_array( $value )
			) {
				// Remove null from array to be handled separately if found
				$includeNull = false;
				foreach ( array_keys( $value, null, true ) as $nullKey ) {
					$includeNull = true;
					unset( $value[$nullKey] );
				}
				if ( count( $value ) == 0 && !$includeNull ) {
					throw new InvalidArgumentException(
						__METHOD__ . ": empty input for field $field" );
				} elseif ( count( $value ) == 0 ) {
					// only check if $field is null
					$list .= "$field IS NULL";
				} else {
					// IN clause contains at least one valid element
					if ( $includeNull ) {
						// Group subconditions to ensure correct precedence
						$list .= '(';
					}
					if ( count( $value ) == 1 ) {
						// Special-case single values, as IN isn't terribly efficient
						// Don't necessarily assume the single key is 0; we don't
						// enforce linear numeric ordering on other arrays here.
						$value = array_values( $value )[0];
						$list .= $field . " = " . $this->quoter->addQuotes( $value );
					} else {
						$list .= $field . " IN (" . $this->makeList( $value ) . ") ";
					}
					// if null present in array, append IS NULL
					if ( $includeNull ) {
						$list .= " OR $field IS NULL)";
					}
				}
			} elseif ( $value === null ) {
				if ( $mode == self::LIST_AND || $mode == self::LIST_OR ) {
					$list .= "$field IS ";
				} elseif ( $mode == self::LIST_SET ) {
					$list .= "$field = ";
				}
				$list .= 'NULL';
			} else {
				if (
					$mode == self::LIST_AND || $mode == self::LIST_OR || $mode == self::LIST_SET
				) {
					$list .= "$field = ";
				}
				$list .= $mode == self::LIST_NAMES ? $value : $this->quoter->addQuotes( $value );
			}
		}

		return $list;
	}

	public function makeWhereFrom2d( $data, $baseKey, $subKey ) {
		$conds = [];

		foreach ( $data as $base => $sub ) {
			if ( count( $sub ) ) {
				$conds[] = $this->makeList(
					[ $baseKey => $base, $subKey => array_map( 'strval', array_keys( $sub ) ) ],
					self::LIST_AND
				);
			}
		}

		if ( $conds ) {
			return $this->makeList( $conds, self::LIST_OR );
		} else {
			// Nothing to search for...
			return false;
		}
	}

	public function factorConds( $condsArray ) {
		if ( count( $condsArray ) === 0 ) {
			throw new InvalidArgumentException(
				__METHOD__ . ": empty condition array" );
		}
		$condsByFieldSet = [];
		foreach ( $condsArray as $conds ) {
			if ( !count( $conds ) ) {
				throw new InvalidArgumentException(
					__METHOD__ . ": empty condition subarray" );
			}
			$fieldKey = implode( ',', array_keys( $conds ) );
			$condsByFieldSet[$fieldKey][] = $conds;
		}
		$result = '';
		foreach ( $condsByFieldSet as $conds ) {
			if ( $result !== '' ) {
				$result .= ' OR ';
			}
			$result .= $this->factorCondsWithCommonFields( $conds );
		}
		return $result;
	}

	/**
	 * Same as factorConds() but with each element in the array having the same
	 * set of array keys. Validation is done by the caller.
	 *
	 * @param array $condsArray
	 * @return string
	 */
	private function factorCondsWithCommonFields( $condsArray ) {
		$first = $condsArray[array_key_first( $condsArray )];
		if ( count( $first ) === 1 ) {
			// IN clause
			$field = array_key_first( $first );
			$values = [];
			foreach ( $condsArray as $conds ) {
				$values[] = $conds[$field];
			}
			return $this->makeList( [ $field => $values ], self::LIST_AND );
		}

		$field1 = array_key_first( $first );
		$nullExpressions = [];
		$expressionsByField1 = [];
		foreach ( $condsArray as $conds ) {
			$value1 = $conds[$field1];
			unset( $conds[$field1] );
			if ( $value1 === null ) {
				$nullExpressions[] = $conds;
			} else {
				$expressionsByField1[$value1][] = $conds;
			}

		}
		$wrap = false;
		$result = '';
		foreach ( $expressionsByField1 as $value1 => $expressions ) {
			if ( $result !== '' ) {
				$result .= ' OR ';
				$wrap = true;
			}
			$factored = $this->factorCondsWithCommonFields( $expressions );
			$result .= "($field1 = " . $this->quoter->addQuotes( $value1 ) .
				" AND $factored)";
		}
		if ( count( $nullExpressions ) ) {
			$factored = $this->factorCondsWithCommonFields( $nullExpressions );
			if ( $result !== '' ) {
				$result .= ' OR ';
				$wrap = true;
			}
			$result .= "($field1 IS NULL AND $factored)";
		}
		if ( $wrap ) {
			return "($result)";
		} else {
			return $result;
		}
	}

	/**
	 * @inheritDoc
	 * @stable to override
	 */
	public function buildConcat( $stringList ) {
		return 'CONCAT(' . implode( ',', $stringList ) . ')';
	}

}
