<?php
/**
 * Rocktile Roof Configuration Rules.
 *
 * Defines calculations rules, required dimension fields, and fixed accessory
 * quantities per roof shape.
 *
 * @package Rocktile_Calculator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rocktile_Calculator_Roof_Config {

	/**
	 * Configuration definitions per roof type/subtype.
	 *
	 * @var array
	 */
	const ROOF_CONFIGS = array(
		// 1. Nyeregtető - Egyszerű nyeregtető
		'gable-simple' => array(
			'name'                 => 'Egyszerű nyeregtető',
			'group'                => 'gable',
			'hasVerge'             => true,
			'fields'               => array( 'roofArea', 'ridge', 'eaves', 'verge' ),
			'ridgeUsesHip'         => false,
			'ridgeExtra'           => 0,
			'starterRidge'         => 0,
			'ridgeEndCap'          => 2,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 2. Nyeregtető - L alakú
		'gable-l' => array(
			'name'                 => 'L alakú nyeregtető',
			'group'                => 'gable',
			'hasVerge'             => true,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'valley', 'eaves', 'verge' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 5,
			'starterRidge'         => 1,
			'ridgeEndCap'          => 2,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 3. Nyeregtető - T alakú
		'gable-t' => array(
			'name'                 => 'T alakú nyeregtető',
			'group'                => 'gable',
			'hasVerge'             => true,
			'fields'               => array( 'roofArea', 'ridge', 'valley', 'eaves', 'verge' ),
			'ridgeUsesHip'         => false,
			'ridgeExtra'           => 0,
			'starterRidge'         => 0,
			'ridgeEndCap'          => 3,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 4. Kontyolt nyeregtető - Egyszerű kontytető
		'hipped-simple' => array(
			'name'                 => 'Egyszerű kontytető',
			'group'                => 'hipped',
			'hasVerge'             => false,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'eaves' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 10,
			'starterRidge'         => 4,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 5. Kontyolt nyeregtető - L alakú kontytető
		'hipped-l' => array(
			'name'                 => 'L alakú kontytető',
			'group'                => 'hipped',
			'hasVerge'             => false,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'valley', 'eaves' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 15,
			'starterRidge'         => 5,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 6. Kontyolt nyeregtető - T alakú kontytető
		'hipped-t' => array(
			'name'                 => 'T alakú kontytető',
			'group'                => 'hipped',
			'hasVerge'             => false,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'valley', 'eaves' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 15,
			'starterRidge'         => 6,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 7. Sátortető
		'pyramid' => array(
			'name'                 => 'Sátortető',
			'group'                => 'pyramid',
			'hasVerge'             => false,
			'fields'               => array( 'roofArea', 'hip', 'eaves' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 5,
			'starterRidge'         => 4,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 8. Manzárdtető - Egyszerű manzárdtető
		'mansard-simple' => array(
			'name'                 => 'Egyszerű manzárdtető',
			'group'                => 'mansard',
			'hasVerge'             => true,
			'fields'               => array( 'roofArea', 'ridge', 'eaves', 'verge' ),
			'ridgeUsesHip'         => false,
			'ridgeExtra'           => 0,
			'starterRidge'         => 0,
			'ridgeEndCap'          => 2,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 9. Manzárdtető - Manzárd kontytető
		'mansard-hipped' => array(
			'name'                 => 'Manzárd kontytető',
			'group'                => 'mansard',
			'hasVerge'             => false,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'eaves' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 10,
			'starterRidge'         => 8,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => false,
		),

		// 10. Egyedi tetőforma
		'custom' => array(
			'name'                 => 'Egyedi tetőforma',
			'group'                => 'custom',
			'hasVerge'             => true,
			'fields'               => array( 'roofArea', 'ridge', 'hip', 'valley', 'eaves', 'verge' ),
			'ridgeUsesHip'         => true,
			'ridgeExtra'           => 0,
			'starterRidge'         => 0,
			'ridgeEndCap'          => 0,
			'repairKit'            => 1,
			'requiresManualReview' => true,
		),
	);

	/**
	 * Get configuration for a roof type / subtype.
	 *
	 * @param string $roofKey
	 * @return array|null
	 */
	public static function get_config( $roofKey ) {
		if ( isset( self::ROOF_CONFIGS[ $roofKey ] ) ) {
			return self::ROOF_CONFIGS[ $roofKey ];
		}
		return null;
	}

	/**
	 * Check if a roof configuration key is valid.
	 *
	 * @param string $roofKey
	 * @return bool
	 */
	public static function is_valid_roof_type( $roofKey ) {
		return isset( self::ROOF_CONFIGS[ $roofKey ] );
	}

	/**
	 * Get human readable roof shape name.
	 *
	 * @param string $roofKey
	 * @return string
	 */
	public static function get_name( $roofKey ) {
		$cfg = self::get_config( $roofKey );
		return $cfg ? $cfg['name'] : (string) $roofKey;
	}

	/**
	 * Get dimension field label and unit.
	 *
	 * @param string $key
	 * @return array
	 */
	public static function get_dimension_label( $key ) {
		$map = array(
			'roofArea' => array( 'label' => 'Tetőfelület', 'unit' => 'm²' ),
			'ridge'    => array( 'label' => 'Gerinc hossza', 'unit' => 'm' ),
			'hip'      => array( 'label' => 'Élgerinc hossza', 'unit' => 'm' ),
			'valley'   => array( 'label' => 'Vápa hossza', 'unit' => 'm' ),
			'eaves'    => array( 'label' => 'Eresz hossza', 'unit' => 'm' ),
			'verge'    => array( 'label' => 'Orom hossza', 'unit' => 'm' ),
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : array( 'label' => (string) $key, 'unit' => '' );
	}

	/**
	 * Get human readable verge option name.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function get_verge_name( $key ) {
		$map = array(
			'under'       => 'Oromszegély',
			'under-cover' => 'Oromszegély',
			'over'        => 'Oromszegély',
			'over-cover'  => 'Oromszegély',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : 'Oromszegély';
	}

	/**
	 * Get human readable ventilation option name.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function get_ventilation_name( $key ) {
		$map = array(
			'standard' => 'Standard pontszellőzés',
			'none'     => 'Nincs külön szellőzés',
		);
		return isset( $map[ $key ] ) ? $map[ $key ] : 'Standard pontszellőzés';
	}
}
