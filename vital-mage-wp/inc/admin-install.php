<?php global $vital_mage_wp; ?>

<div class="wrap clearfix">

	<?php require_once('admin-sidebar.php'); ?>
	
	<div id="mwi_left">
		<div id="poststuff" class="fleft">
			<h2 class="mwi-title"><?php echo $vital_mage_wp->name; ?></h1>
			<p><?php _e('The modified functions.php in Magento could not be found. It is very important this is created before MWI will work. Please see the instructions below.', $vital_mage_wp->slug); ?></p>
			<h2><?php _e('Installation', $vital_mage_wp->slug); ?></h2>
			
			<ol>
			    <li><?php _e('Navigate to ~/your-magento/app/code/core/Mage/Core/functions.php', $vital_mage_wp->slug); ?></li>
			    <li><?php _e('Duplicate that file and place the new version in ~/your-magento/app/code/local/Mage/Core/functions.php – this file will now be used over the original, and will remain during Magento upgrades. If the destination folders do not exist, you can create them (maintain the capital lettering).', $vital_mage_wp->slug); ?></li>
			    <li><?php _e('Open the newly created file and browse to around line 90, where you will find this:', $vital_mage_wp->slug); ?><br>
                    <code>function __() { return Mage::app()->getTranslator()->translate(func_get_args()); }</code>
			    </li>
			    <li><?php _e('Replace the entire function, which usually spans over approximately 3 lines, with:', $vital_mage_wp->slug); ?><br>
			        <code>if(!function_exists('__')) { function __() { return Mage::app()->getTranslator()->translate(func_get_args()); } }</code>
			    </li>
			    <li><?php _e('Upload the file to your server, and you are done!', $vital_mage_wp->slug); ?></li>
			</ol>
			
			<p><?php _e("Once you've done the above you'll be able to modify the MWI settings here.", $vital_mage_wp->slug); ?></p>
		</div>
	</div>
	
</div>