<?php

namespace Pluginever\WCSerialNumberPro\Admin;


// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table')) {
	require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Create a new table class that will extend the WP_List_Table
 */
class Generate_Serial_Table extends \WP_List_Table
{

	protected $is_single = false;
	protected $search_query = false;

	/** Class constructor */
	public function __construct($post_id = '')
	{

		parent::__construct([
			'singular' => __('Generate Serial Number', 'wc-serial-number'), //singular name of the listed records
			'plural'   => __('Generate Serial Numbers', 'wc-serial-number'), //plural name of the listed records
			'ajax'     => false //should this table support ajax?

		]);

		$this->is_single = $post_id;

		//Search based on serial number
		empty($_GET['s']) ? false : $this->search_query = $_GET['s'];

	}

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */

	public function prepare_items()
	{
		$columns  = $this->get_columns();
		$sortable = $this->get_sortable_columns();
		$data     = $this->table_data();
		usort($data, array(&$this, 'sort_data'));
		$perPage     = 15;
		$currentPage = $this->get_pagenum();
		$totalItems  = count($data);
		$this->set_pagination_args(array(
			'total_items' => $totalItems,
			'per_page'    => $perPage
		));
		$data                  = array_slice($data, (($currentPage - 1) * $perPage), $perPage);
		$this->_column_headers = array($columns, $sortable);
		$this->items           = $data;

	}

	/**
	 * Override the parent columns method. Defines the columns to use in your listing table
	 *
	 * @return array
	 */
	public function get_columns()
	{
		$columns = array();
		if (!$this->is_single) {
			$columns = array(
				'cb'            => '<input type="checkbox" />',
				'product'       => __('Product', 'wc-serial-numbers'),
				'variation'     => __('Variation', 'wc-serial-numbers'),
				'prefix'        => __('Prefix. ', 'wc-serial-numbers'),
				'chunks_number' => __('Chunks', 'wc-serial-numbers'),
				'chunks_length' => __('Chunks', 'wc-serial-numbers'),
				'suffix'        => __('Suffix', 'wc-serial-numbers'),
				'instance'      => __('Instance', 'wc-serial-numbers'),
				'validity'      => __('Validity', 'wc-serial-numbers'),
				'generate'      => __('Generate', 'wc-serial-numbers'),
			);
		}

		return $columns;
	}


	/**
	 * Define the sortable columns
	 *

	 */
	public function get_sortable_columns()
	{
		return [
			'product'       => array('product', false),
			'variation'     => array('variation', false),
			'chunks_number' => array('chunks_number', false),
			'chunks_length' => array('chunks_length', false),
			'validity'      => array('validity', false),
		];
	}

	/**
	 * Get the table data
	 *
	 * @return array
	 */
	private function table_data()
	{
		$data = array();

		$query = !$this->is_single ? ['s' => $this->search_query] : ['meta_key' => 'product', 'meta_value' => $this->is_single];

		$posts = wsnp_get_generator_rules($query);

		foreach ($posts as $post) {

			setup_postdata($post);

			$product       = get_post_meta($post->ID, 'product', true);
			$variation     = get_post_meta($post->ID, 'variation', true);
			$prefix        = get_post_meta($post->ID, 'prefix', true);
			$chunks_number = get_post_meta($post->ID, 'chunks_number', true);
			$chunk_length  = get_post_meta($post->ID, 'chunk_length', true);
			$suffix        = get_post_meta($post->ID, 'suffix', true);
			$instance      = get_post_meta($post->ID, 'max_instance', true);
			$validity      = get_post_meta($post->ID, 'validity', true);
			$generate_num  = wsn_get_settings('wsn_generate_number', '', 'wsn_serial_generator_settings');

			$generate_html = '<input type="number" class="generate_number ever-thumbnail-small" name="generate_number" id="generate_number" value="'.$generate_num.'">
			<button class="button button-primary wsn_generate_btn" data-rule_id="'.$post->ID.'"> '.__('Generate','wc-serial-numbers').'</button>
			';

			$data[] = [
				'ID'            => $post->ID,
				'product'       => '<a href="' . get_edit_post_link($product) . '">' . get_the_title($product) . '</a>',
				'variation'     => empty($variation) ? __('Main Product', 'wc-serial-number') : get_the_title($variation),
				'prefix'        => empty($prefix) ? '' : $prefix,
				'chunks_number' => empty($chunks_number) ? '' : $chunks_number,
				'chunks_length' => empty($chunk_length) ? '' : $chunk_length,
				'suffix'        => empty($suffix) ? '' : $suffix,
				'instance'      => empty($instance) ? '∞' : $instance,
				'validity'      => empty($validity) ? '∞' : $validity,
				'generate'      => $generate_html,
			];

		}

		return $data;
	}


	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  array $item Data
	 * @param  String $column_name - Current column name
	 *
	 * @return Mixed
	 */
	public function column_default($item, $column_name)
	{

		switch ($column_name) {
			case 'ID':
			case 'product':
			case 'variation':
			case 'prefix':
			case 'chunks_number':
			case 'chunks_length':
			case 'suffix':
			case 'instance':
			case 'validity':
			case 'generate':
				return $item[$column_name];
			default:
				return print_r($item, true);
		}
	}

	/**
	 * Returns an associative array containing the bulk action
	 *
	 * @return array
	 */

	public function get_bulk_actions()
	{
		if (!$this->is_single) {
			$actions = [
				'bulk-delete' => 'Delete'
			];

			return $actions;
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb($item)
	{
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['ID']
		);
	}

	function column_product($item)
	{
		$actions = array(
			'edit'   => '<a href="' . add_query_arg(['type' => 'automate', 'row_action' => 'edit', 'generator_rule' => $item['ID']], WPWSN_ADD_GENERATE_RULE) . '">' . __('Edit', 'wc-serial-number') . '</a>',
			'delete' => '<a href="' . add_query_arg(['row_action' => 'delete', 'generator_rule' => $item['ID']], WPWSN_GENERATE_SERIAL_PAGE) . '">' . __('Delete', 'wc-serial-number') . '</a>',
		);

		return sprintf('%1$s %2$s', $item['product'], $this->row_actions($actions));
	}

	/**
	 * Allows you to sort the data by the variables set in the $_GET
	 *
	 * @return Mixed
	 */
	private function sort_data($a, $b)
	{
		// Set defaults
		$orderby = 'product';
		$order   = 'asc';

		// If orderby is set, use this as the sort column
		if (!empty($_GET['orderby'])) {
			$orderby = esc_attr($_GET['orderby']);
		}
		// If order is set use this as the order
		if (!empty($_GET['order'])) {
			$order = esc_attr($_GET['order']);
		}

		$result = strcmp($a[$orderby], $b[$orderby]);
		if ($order === 'asc') {
			return $result;
		}

		return -$result;
	}


}