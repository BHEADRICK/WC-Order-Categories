<?php
class ATF_Controller {
	/**
	 * Current post type.
	 */
	public $post_type;

	public function __construct() {
		add_action( 'restrict_manage_posts', array( $this, 'output_filters' ) );
        add_action('pre_get_posts', [$this, 'filter_orders']);
        add_action('init', [$this, 'register_order_cats']);
        add_action('woocommerce_order_status_on-hold', [$this, 'set_order_cat']);
        add_filter('bulk_actions-edit-shop_order', [$this, 'bulk_action']);
        add_filter('handle_bulk_actions-edit-shop_order', [$this, 'handle_bulk_action'], 10, 3);
        add_filter('manage_shop_order_posts_columns', [$this, 'order_columns'], 105);
        add_action('manage_shop_order_posts_custom_column', [$this, 'order_column_content']);
	}

    public function order_column_content($column){
        global $post;
        if( 'product_cat' === $column) {

            $terms = get_the_terms($post_id, 'product_cat');
            echo implode(', ', array_map(function ($term) {
                return $term->name;
            }, $terms));
        }else{
            error_log(print_r(compact('column'), true));
        }
    }

    public function order_columns($columns){
        $columns['product_cat'] = 'Categories';
        return $columns;
    }

    public function handle_bulk_action($redirect_url, $action, $post_ids) {
        if ($action == 'process-order-cats') {
            foreach ($post_ids as $post_id) {
              $this->set_order_cat($post_id);
            }
            $redirect_url = add_query_arg('changed-to-published', count($post_ids), $redirect_url);
        }
        return $redirect_url;
    }

    public function bulk_action($bulk_actions) {
        $bulk_actions['process-order-cats'] = __('Update Order Categories', 'poolwarehouse');
        return $bulk_actions;
    }

    public function set_order_cat($order_id){
        $order = wc_get_order($order_id);

        $items = $order->get_items();

        $top_cats = [];
        $top_price = 0;
        $top_name = '';
        foreach($items as $item){

            $product = $item->get_product();
            if(!$product){

                continue;
            }
                $price = $item->get_subtotal();
            if( $price > $top_price){

                $top_price =  $price;
                $top_name = $product->get_title();

                if ($product->get_parent_id() > 0) {
                    $top_cats = get_the_terms($product->get_parent_id(), 'product_cat');
                } else {
                    $top_cats = get_the_terms($product->get_id(), 'product_cat');
                }
            }

        }
        if(!empty($top_cats)) {
            if($top_cats[0]->name === 'Uncategorized' && strpos($top_name, 'Pool Kit')!== false){

                $categories = [165];
            }else{

                $categories = array_map(function ($term) {
                    return $term->term_id;
                }, $top_cats);

            }
            wp_set_post_terms($order_id, $categories, 'product_cat');
        }
    }

    public function register_order_cats(){
        register_taxonomy_for_object_type('product_cat', 'shop_order');
    }

    public function filter_orders($query){
    
        global $post_type, $pagenow;
        if($pagenow == 'edit.php' && $post_type == 'shop_orders') {
            if (isset($_GET['product_cat'])) {


            }
        }
    }

	/**
	 * Output filters in the All Posts screen.
	 *
	 * @param string $post_type The current post types.
	 */
	public function output_filters( $post_type ) {
        wp_dropdown_categories( array(
            'show_option_all' => sprintf( __( 'All %s', 'admin-taxonomy-filter' ), 'Product Categories' ),
            'orderby'         => 'name',
            'order'           => 'ASC',
            'hide_empty'      => false,
            'hide_if_empty'   => true,
            'selected'        => filter_input( INPUT_GET, 'product_cat', FILTER_SANITIZE_STRING ),
            'hierarchical'    => true,
            'name'            => 'product_cat',
            'taxonomy'        => 'product_cat',
            'value_field'     => 'slug',
        ) );
	}

	/**
	 * Check if we have some taxonomies to filter.
	 *
	 * @param \WP_Taxonomy $taxonomy The taxonomy object.
	 *
	 * @return bool
	 */
	protected function is_filterable( $taxonomy ) {
		// Post category is filterable by default.


        error_log(print_r($taxonomy, true));
		$option = get_option( 'admin_taxonomy_filter' );
		return isset( $option[ $this->post_type ] ) && in_array( $taxonomy->name, (array) $option[ $this->post_type ], true );
	}

	/**
	 * Output filter for a taxonomy.
	 *
	 * @param \WP_Taxonomy $taxonomy The taxonomy object.
	 */
	protected function output_filter_for( $taxonomy ) {
		wp_dropdown_categories( array(
			'show_option_all' => sprintf( __( 'All %s', 'admin-taxonomy-filter' ), $taxonomy->label ),
			'orderby'         => 'name',
			'order'           => 'ASC',
			'hide_empty'      => false,
			'hide_if_empty'   => true,
			'selected'        => filter_input( INPUT_GET, $taxonomy->query_var, FILTER_SANITIZE_STRING ),
			'hierarchical'    => true,
			'name'            => $taxonomy->query_var,
			'taxonomy'        => $taxonomy->name,
			'value_field'     => 'slug',
		) );
	}
}
