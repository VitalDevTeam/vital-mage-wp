<?php global $vital_mage_wp; ?>

<div class="wrap clearfix">
	<?php if(isset($_POST['sync-product']) && $_POST['sync-product']):
		$mage_php_url = $this->get_mage_path();

	    if ( !empty( $mage_php_url ) && file_exists( $mage_php_url ) && !is_dir( $mage_php_url )) :
	        // Include Magento's Mage.php file
	        require_once ( $mage_php_url );
	       	umask(0);
			Mage::app();
	    endif;
		$visibility = array(
						   Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
						   Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
						);

		$category = Mage::getModel('catalog/category');
		$productCollection = $category->getProductCollection()
								->addAttributeToSelect('*')
								->addAttributeToFilter('status', array('eq' => 1))
								->addAttributeToFilter('visibility', $visibility);

		$storeProduct = '';
		foreach ($productCollection as $_product) {
			$storeProduct[$_product->getId()] = array('sku' => $_product->getSku(), 'name' => $_product->getName(), 'categories' => $_product->getCategoryIds());
		}

		$status = file_put_contents(WP_PLUGIN_DIR.'/acf-magento-product/fields/products.json', json_encode($storeProduct));
		if($status): ?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e('Products successfully synced', $vital_mage_wp->slug); ?></p>
			</div>
		<?php else:?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e('Something went wrong', $vital_mage_wp->slug); ?></p>
			</div>
		<?php endif; ?>
	<?php endif;?>
	<div id="mwi_left">
		<div class="fleft">
			<h2 class="mwi-title"><?php echo $vital_mage_wp->name; ?></h2>
			<form method="post" action="options.php">
    			<?php settings_fields('vital-magewp-main-settings'); ?>

    			<?php

    			$mwiSettings = array();

    			$checkMage = $this->check_mage();

    			$mwiSettings[] = array(
        		    'title'         =>  __('Mage.php Path', $vital_mage_wp->slug),
        		    'description'   =>  array(
            		                        __('Enter the path (absolute or relative from WordPress root) to your Mage.php file.', $vital_mage_wp->slug),
        		                            sprintf(__('Your public/www root is: %s', $vital_mage_wp->slug), $_SERVER['DOCUMENT_ROOT'])
                                        ),
        		    'name'          =>  'magepath',
        		    'type'          =>  'text',
        		    'value'         =>  $vital_mage_wp->getValue('magepath', $_SERVER['DOCUMENT_ROOT']),
        		    'additional'    =>  '<div class="'.$checkMage['class'].'">'.$checkMage['message'].'</div>'
    			);

    			$mwiSettings[] = array(
        		    'title'         =>  __('Package Name', $vital_mage_wp->slug),
        		    'description'   =>  '',
        		    'name'          =>  'package',
        		    'type'          =>  'text',
        		    'value'         =>  $vital_mage_wp->getValue('package', 'default'),
        		    'additional'    =>  ''
    			);

    			$mwiSettings[] = array(
        		    'title'         =>  __('Theme Name', $vital_mage_wp->slug),
        		    'description'   =>  '',
        		    'name'          =>  'theme',
        		    'type'          =>  'text',
        		    'value'         =>  $vital_mage_wp->getValue('theme', 'default'),
        		    'additional'    =>  ''
    			);

    			$mwiSettings['websitecode'] = array(
        		    'title'         =>  __('Magento Website Code', $vital_mage_wp->slug),
        		    'description'   =>  array(
        		                            __('Enter the Magento website code to get blocks and sessions from. You can see all available website codes to the right. The default is usually base.', $vital_mage_wp->slug),
        		                            ( !class_exists('Mage') ? __('The table of available website codes will appear to the right once the path to Mage.php is saved and correct.', $vital_mage_wp->slug) : '' )
                                        ),
        		    'name'          =>  'websitecode',
        		    'type'          =>  'text',
        		    'value'         =>  $vital_mage_wp->getValue('websitecode', 'base'),
        		    'additional'    =>  ''
    			);

    			if($checkMage['result'] == true):

        			$codes = '<h4>Available Magento Websites</h4>';

                    $codes .= '<table>';

                        $codes .= '<tr>';
                            $codes .= '<th>Name</th>';
                            $codes .= '<th>Code</th>';
                        $codes .= '</tr>';

                        $allStores = Mage::app()->getWebsites();
                        foreach ($allStores as $_eachStoreId => $val):

                            $_storeCode = Mage::app()->getWebsite($_eachStoreId)->getCode();
                            $_storeName = Mage::app()->getWebsite($_eachStoreId)->getName();
                            $_storeId = Mage::app()->getWebsite($_eachStoreId)->getId();

                            $codes .= '<tr>';
                                $codes .= '<td>'.$_storeName.'</td>';
                                $codes .= '<td>'.$_storeCode.'</td>';
                            $codes .= '</tr>';

                        endforeach;

                    $codes .= '</table>';

        			$mwiSettings['websitecode']['additional'] = $codes;

    			endif; ?>

    			<div class="postbox mwi_settings">

        			<h3><?php _e('Main Settings',$vital_mage_wp->slug); ?></h3>

        			<div class="inside">

        				<table class="form-table">

        				  <tbody>

        				    <?php foreach($mwiSettings as $mwiSetting): ?>

        				        <tr valign="top">
                                    <th scope="row">

                                        <strong><?php echo $mwiSetting['title']; ?></strong>

                                        <?php if(is_array($mwiSetting['description']) && !empty($mwiSetting['description'])): ?>
                                            <div class="description">
                                                <?php foreach($mwiSetting['description'] as $paragraph): ?>
                                                    <p><?php echo $paragraph; ?></p>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                    </th>

                                    <td>

                                        <?php if( $mwiSetting['type'] == "text" ): ?>

                                            <input class="regular-text" type="text" name="vital_magewp_options[<?php echo $mwiSetting['name']; ?>]" value="<?php echo $mwiSetting['value']; ?>" />

                                        <?php elseif( $mwiSetting['type'] == "checkbox" ): ?>

                                            <input name="vital_magewp_options[<?php echo $mwiSetting['name']; ?>]" type="checkbox" value="1" <?php checked( $mwiSetting['value'], 1 ); ?>/>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php echo $mwiSetting['additional']; ?>

                                    </td>
                                </tr>

                            <?php endforeach; ?>

        				  </tbody>
        				</table>

        			</div><!-- /.inside -->

    			</div><!-- /.postbox -->


                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Save Changes', $vital_mage_wp->slug); ?>" />
                </p>

            </form>

		</div><!-- /fleft -->
	</div><!-- /#mwi_left -->
	<div class="update-magento-product">
		<h2>Sync Magento Products Data</h2>
		<div class="product-progress">
			<form method="post" action="">
				<p>You can sync the Magento product data for ACF Magento Products</p>
				<input type="hidden" name="sync-product" value="1" />
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Sync Products Data', $vital_mage_wp->slug); ?>" />
				</p>
			</form>
		</div>
	</div>
</div>
