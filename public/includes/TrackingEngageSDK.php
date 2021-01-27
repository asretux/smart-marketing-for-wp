<?php


class TrackingEngageSDK
{
    protected $client_id;
    protected $list_id;
    protected $social_id;
    protected $order_id;
    const OPTION_FLAG = 'order_trigger_';
    const SESSION_TAG = 'egoi_tracking_uid';

    public function __construct($client_id, $list_id, $order_id = false, $social_id = null)
    {
        if(!empty($list_id)){$this->list_id = $list_id;}
        if(!empty($client_id)){$this->client_id = $client_id;}
        if(!empty($order_id)){ $this->order_id = $order_id; }
        if(!empty($social_id)){ $this->social_id = $social_id; }
    }

    public function getStartUp(){
        $this->setOrder();
        if(!isset($_GET['wc-ajax'])) {
            ?>
            <script>
                (function () {
                        window._egoiaq = window._egoiaq || [];
                        var url = (("https:" == document.location.protocol) ? "https://egoimmerce.e-goi.com/" : "http://egoimmerce.e-goi.com/");
                        var url_cdn = (("https:" == document.location.protocol) ? "https://cdn-te.e-goi.com/" : "http://cdn-te.e-goi.com/");
                        var d = document, g = d.createElement('script'), s = d.getElementsByTagName('script')[0];
                        g.type = 'text/javascript';
                        g.defer = true;
                        g.async = true;
                        g.src = url_cdn + 'egoimmerce.js';
                        s.parentNode.insertBefore(g, s);
                        window._egoiaq.push(['setClientId', <?php echo $this->client_id ?>]);
                        window._egoiaq.push(['setListId', <?php echo $this->list_id ?>]);
                        <?php if($this->checkSubscriber() !== false){ ?>window._egoiaq.push(['setSubscriber', "<?php echo $this->checkSubscriber(); ?>"]);<?php } ?>

                        window._egoiaq.push(['setTrackerUrl', url + 'collect']);
                        window._egoiaq.push(['trackPageView']);
                    }
                )();
            </script>
            <?php
        }
        if (!class_exists('WooCommerce')) {return false;}
        $this->getProductView();
        $this->getProductsInCart();
    }

    public function getStartUpSocial(){
        if(!isset($_GET['wc-ajax']) && !empty($this->social_id)) {
            ?><script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
                new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
                j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
                'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer','<?php echo $this->social_id ?>');</script>
           <?php
        }
    }

    public function getProductLdJSON(){
        $product = wc_get_product( get_the_id() );
        
        $price = $product->get_sale_price() ? $product->get_sale_price() : $product->get_price();
        if ( '' !== $price) {
            if ( $product->is_type( 'variable' ) ) {
                $price = $product->get_variation_price( 'min', false );
            }
        }
        ?>
        <script type="application/ld+json" class="egoi-smart-marketing">
            {
            "@context":"https://schema.org",
            "@type":"Product",
            "productID":"<?php echo $product->get_id(); ?>",
            "name":"<?php echo addslashes($product->get_name()); ?>",
            "description":"<?php echo addslashes(wp_strip_all_tags( do_shortcode( $product->get_short_description() ? $product->get_short_description() : $product->get_description() ) ) );?>",
            "url":"<?php echo $product->get_permalink(); ?>",
            "image":"<?php echo wp_get_attachment_image_url( $product->get_image_id(), 'full'); ?>",
            "brand":"<?php echo $product->get_meta( '_egoi_brand' ); ?>",
            "offers": [
                {
                "@type": "Offer",
                "price": "<?php echo $price; ?>",
                "priceCurrency": "<?php echo get_woocommerce_currency(); ?>",
                "itemCondition": "https://schema.org/NewCondition",
                "availability": "<?php echo 'http://schema.org/' . ( $product->is_in_stock() ? 'InStock' : 'OutOfStock' ) ?>"
                }
            ]
            }
            </script>
            <script>var egoi_product = { 'id':'<?php echo $product->get_id(); ?>', 'client_id':'<?php echo $this->client_id ?>','name':'<?php echo addslashes($product->get_name()); ?>','price':'<?php echo $price; ?>'};</script>
        <?php
    }

    protected function getProductView(){
        if(!is_product()){ return false; }
        $product = wc_get_product( get_the_id() );

        ?>

            <script>

                window._egoiaq.push(['setEcommerceView',
                    "<?php echo $product->get_id(); ?>",
                    "<?php echo $product->get_name(); ?>",
                    "",
                    <?php echo (double) $product->get_price(); ?>
                ]);
                window._egoiaq.push(['trackPageView']);

            </script>

        <?php
    }

    protected function getProductsInCart(){
        if(!empty($this->order_id)){return false;}
        $cart = WC()->cart->get_cart();
        require_once(plugin_dir_path( __FILE__ ) . '../../includes/class-egoi-for-wp-products-bo.php');
        $options_catalogs = EgoiProductsBo::getCatalogOptions();
        $options_catalogs = empty($options_catalogs)?[]:$options_catalogs;
        $variation = false;
        foreach ($options_catalogs as $options_catalog){
            $variation = $variation || (empty($options_catalog['variations'])?false:true) ;
        }
        foreach ( $cart as $cart_item ) {
            ?>
            <script>
                window._egoiaq.push(['addEcommerceItem',
                    "<?php echo ($variation && !empty($cart_item['variation_id'])) ?$cart_item['variation_id']:$cart_item['product_id']; ?>",
                    "<?php echo $cart_item['data']->get_title(); ?>",
                    "",
                    <?php echo (double) $cart_item['data']->get_price(); ?>,
                    <?php echo (int) $cart_item['quantity']; ?>
                ]);
            </script>
            <?php
        }

        if(count($cart) == 0){ return false; }

        ?>

        <script>
            window._egoiaq.push(['trackEcommerceCartUpdate',
                <?php echo (double) WC()->cart->cart_contents_total; ?>]);

            window._egoiaq.push(['trackPageView']);
        </script>

        <?php
        return true;
    }

    public function getOrder(){
        if(empty($this->order_id)){return false;}

        $order = get_option(self::OPTION_FLAG.$this->order_id);
        if(empty($order)){ return false; }

        $this->printOrder($order);

        delete_option(self::OPTION_FLAG.$this->order_id);

        return true;
    }

    public function setOrder(){
        if(empty($this->order_id)){return false;}
        $order = wc_get_order( $this->order_id );
        update_option(self::OPTION_FLAG.$this->order_id, $order);
        return true;
    }

    /**
     * @return bool | string
     */
    private function checkSubscriber(){
        if(!empty(self::getUserMeta())){
            return self::getUserMeta();
        }
        if(isset($_SESSION[self::SESSION_TAG])){return $_SESSION[self::SESSION_TAG]; }
        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ){ return false; }
        return $current_user->user_email;
    }

    private function printOrder($order){

        $order_id  = $order->get_id(); // Get the order ID
        $items = $order->get_items();
        if(!is_array($items)){ $items = []; }

        foreach ($items as $item){

            ?>

            <script>
                window._egoiaq.push(['addEcommerceItem',
                    "<?php echo $item->get_product_id(); ?>",
                    "<?php echo $item->get_name(); ?>",
                    "",
                    <?php echo (double) $item->get_subtotal(); ?>,
                    <?php echo (int) $item->get_quantity(); ?>
                ]);
            </script>

            <?php
        }

        if(count($items)>0){
            ?>
            <script>
                window._egoiaq.push(['trackEcommerceOrder',
                    "<?php echo $order_id; ?>", // (required) Unique Order ID
                    <?php echo (double) $order->get_total(); ?>, // (required) Order Revenue grand total (includes tax, shipping, and subtracted discount)
                    <?php echo (double) $order->get_subtotal(); ?>, // (optional) Order sub total (excludes shipping)
                    <?php echo (double) $order->get_total_tax(); ?>, // (optional) Tax amount
                    <?php echo (double) $order->get_shipping_total(); ?>, // (optional) Shipping amount
                    <?php echo !empty($order->get_total_discount()); ?> // (optional) Discount offered (set to false for unspecified parameter)
                ]);

                window._egoiaq.push(['trackPageView']);
            </script>
            <?php
        }

        return true;

    }

    public static function setUidSession($uid){
        if( is_array($uid) && isset($uid['UID'])){
            $_SESSION[self::SESSION_TAG] = $uid['UID'];
            self::setUserMeta($uid['UID']);
        }else if(  !is_array($uid) ){
            $_SESSION[self::SESSION_TAG] = $uid;
            self::setUserMeta($uid);
        }
    }

    private static function setUserMeta($uid){
        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ){ return false; }
        update_user_meta($current_user->ID, self::SESSION_TAG, $uid);
    }

    private static function getUserMeta(){
        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ){ return false; }
        return get_user_meta($current_user->ID, self::SESSION_TAG, true);
    }

}