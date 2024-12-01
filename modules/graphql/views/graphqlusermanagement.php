<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>

<link href="<?php echo base_url('modules/graphql/assets/main.css'); ?>" rel="stylesheet" type="text/css" />

<div id="wrapper">
   <div class="content">
      <div class="row">
         <div class="col-md-6">
            <div class="panel_s">
               <div class="panel-body">
				<h4><?php echo _l('graphqlapi_token'); ?></h4><hr>
				<?php $graphqltoken =  get_option('graphqltoken'); echo htmlentities($graphqltoken); ?>
               </div>
            </div>
			<a href="<?php echo admin_url('graphql/graphqlusermanagement/regenerate_token'); ?>" class="btn btn-info pull-left display-block"><?php echo _l('graphqlapi_regenerate_token'); ?></a>
         </div>
      </div>
   </div>
</div>


<?php init_tail(); ?>
