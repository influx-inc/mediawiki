<?php

use Symfony\Component\Yaml\Yaml;

require_once __DIR__ . '/Maintenance.php';
require_once __DIR__ . '/includes/ConfigSchemaDerivativeTrait.php';

/**
 * Maintenance script that generates a YAML file containing a JSON Schema representation
 * of the config schema.
 *
 * @ingroup Maintenance
 */
class GenerateConfigSchemaYaml extends Maintenance {
	use ConfigSchemaDerivativeTrait;

	/** @var string */
	private const DEFAULT_OUTPUT_PATH = __DIR__ . '/../docs/config-schema.yaml';

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Generate config-schema.yaml' );

		$this->addOption(
			'output',
			'Output file. Default: ' . self::DEFAULT_OUTPUT_PATH,
			false,
			true
		);
	}

	public function execute() {
		$schemas = $this->loadSchema();

		foreach ( $schemas as &$sch ) {
			// Cast empty arrays to objects if they are declared to be of type object.
			// This ensures they get represented in yaml as {} rather than [].
			if ( isset( $sch['default'] ) && isset( $sch['type'] ) ) {
				$types = (array)$sch['type'];
				if ( $sch['default'] === [] && in_array( 'object', $types ) ) {
					$sch['default'] = new stdClass();
				}
			}

			// Wrap long deprecation messages
			if ( isset( $sch['deprecated'] ) ) {
				$sch['deprecated'] = wordwrap( $sch['deprecated'] );
			}
		}

		$yamlFlags = Yaml::DUMP_OBJECT_AS_MAP
			| Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
			| Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE;

		$array = [ 'config-schema' => $schemas ];
		$yaml = Yaml::dump( $array, 4, 4, $yamlFlags );

		$this->writeOutput( self::DEFAULT_OUTPUT_PATH, $yaml );
	}
}

$maintClass = GenerateConfigSchemaYaml::class;
require_once RUN_MAINTENANCE_IF_MAIN;
