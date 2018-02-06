<?php global $vital_mage_wp;
if(current_user_can('administrator') ) :
	$tab_active = 'settings';
else :
	$tab_active = 'sync';
endif;?>

<div class="wrap clearfix">
	<?php if(isset($_POST['magento-sync']) && $_POST['magento-sync']):
		$tab_active = 'sync';
		$sync_type = sanitize_text_field( $_POST['magento-sync'] );
		$mage_php_url = $this->get_mage_path();

		//Check if the magento avialable
		if ( !empty( $mage_php_url ) && file_exists( $mage_php_url ) && !is_dir( $mage_php_url )) :
	        // Include Magento's Mage.php file
	        require_once ( $mage_php_url );
	       	umask(0);
			Mage::app();
	    endif;

		//If the sync type is product
		if($sync_type === 'product') :
			$visibility = array(
							   Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
							   Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
							);

			$category = Mage::getModel('catalog/category');
			$productCollection = $category->getProductCollection()
									->addAttributeToSelect('*')
									->addAttributeToFilter('status', array('eq' => 1))
									->addAttributeToFilter('type_id', array('eq' => 'grouped'))
									->addAttributeToFilter('visibility', $visibility);

			$storeProduct = '';
			foreach ($productCollection as $_product) {
				$storeProduct[$_product->getId()] = array('sku' => $_product->getSku(), 'name' => $_product->getName(), 'categories' => $_product->getCategoryIds());
			}

			$status = file_put_contents(WP_PLUGIN_DIR.'/acf-magento-product/fields/products.json', json_encode($storeProduct));
		else :
			//If the sync type is Category
			$_helper = Mage::helper('catalog/category');
		    $categoryCollection = $_helper->getStoreCategories();

		    $storeCategories = array();
		    $loop = 0;
			foreach($categoryCollection as $_category) : $_category = Mage::getModel('catalog/category')->load($_category->getId());
			    $storeCategories[$loop]['id'] = $_category->getId();
			    $storeCategories[$loop]['name'] = $_category->getName();
			    $_subcategories = $_category->getChildrenCategories();
			    if (count($_subcategories) > 0) :
			        $subCategories = array();
			        $subLoop = 0;
			        foreach($_subcategories as $_subcategory) :
			            $_subcategory = Mage::getModel('catalog/category')->load($_subcategory->getId());
			            $subCategories[$subLoop]['id'] = $_subcategory->getId();
			            $subCategories[$subLoop]['name'] = $_subcategory->getName();
			            $_sub_subcategories = $_subcategory->getChildrenCategories();
			            if (count($_sub_subcategories) > 0) :
			                $subSubCategory = array();
			                $subSubLoop = 0;
			                foreach($_sub_subcategories as $_sub_subcategory) :
			                    $_sub_subcategory = Mage::getModel('catalog/category')->load($_sub_subcategory->getId());
			                    $subSubCategory[$subSubLoop]['id'] = $_sub_subcategory->getId();
			                    $subSubCategory[$subSubLoop]['name'] = $_sub_subcategory->getName();
			                    $subSubLoop++;
			                endforeach;
			                $subCategories[$subLoop]['child-list'] = $subSubCategory;
			            endif;
			            $subLoop++;
			        endforeach;
			        $storeCategories[$loop]['child-list'] = $subCategories;
			    endif;
			    $loop++;
			endforeach;

			$status = file_put_contents(WP_PLUGIN_DIR.'/acf-magento-category/fields/categories.json', json_encode($storeCategories));
		endif;
		if($status): ?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e('Data successfully synced', $vital_mage_wp->slug); ?></p>
			</div>
		<?php else:?>
			<div class="notice notice-error is-dismissible">
				<p><?php _e('Something went wrong', $vital_mage_wp->slug); ?></p>
			</div>
		<?php endif; ?>
	<?php endif;?>
	<h1 class="mwi-title"><?php echo $vital_mage_wp->name; ?></h1>

	<div class="nav-tab-wrapper">
		<?php if(current_user_can('administrator') ) : ?>
			<a href="#mage-wp-settings" class="nav-tab<?php echo ($tab_active === 'settings') ? ' nav-tab-active' : '' ;?>">Settings</a>
		<?php endif; ?>
		<a href="#sync-product-category" class="nav-tab<?php echo ($tab_active === 'sync') ? ' nav-tab-active' : '' ;?>">Sync Products/Categories</a>
	</div>
	<script>
		// Change tab on click.
		jQuery( '.nav-tab-wrapper .nav-tab[href^="#"]' ).click( function(e){ /* ignores any non hashtag links since they go direct to a URL... */

			e.preventDefault();

			// Hide all tab blocks.
			thisTabBlock = jQuery(this).closest( '.nav-tab-wrapper' );
			// Update selected tab.
			thisTabBlock.find( '.nav-tab-active' ).removeClass( 'nav-tab-active' );
			jQuery(this).addClass( 'nav-tab-active' );

			// Show the correct tab block.
			jQuery( '.tab-content' ).removeClass('tab-content-active');
			jQuery( jQuery(this).attr( 'href' ) ).addClass('tab-content-active');
		});
	</script>
	<?php if(current_user_can('administrator') ) : ?>
		<div id="mage-wp-settings" class="tab-content mage-wp-settings<?php echo ($tab_active === 'settings') ? ' tab-content-active' : '' ;?>">
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

		</div>
	<?php endif;?>
	<div id="sync-product-category" class="tab-content sync-product-category<?php echo ($tab_active === 'sync') ? ' tab-content-active' : '' ;?>">
		<form method="post" action="">
			<div class="postbox mwi_settings">
				<div class="inside sync-progress">
					<p>You can sync the Magento Category/Product data for ACF Magento Fields</p>
					<div class="fieldset">
						<div class="field">
							<input type="radio" id="magento-sync-category" name="magento-sync" value="category" />
							<label for="magento-sync-category">Category</label>
						</div>
						<div class="field">
							<input type="radio" id="magento-sync-product" name="magento-sync" value="product" />
							<label for="magento-sync-product">Product</label>
						</div>
					</div>
				</div>
			</div>
			<p class="submit">
				<input type="submit" class="button-primary" value="<?php _e('Sync Data', $vital_mage_wp->slug); ?>" />
			</p>
		</form>
	</div>
</div>
