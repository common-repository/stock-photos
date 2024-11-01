<?php
/*
Plugin Name: Stock Photos
Plugin URI: https://www.themely.com/plugins/stock-photos/
Description: Search for high-quality and completely free stock photos and import them directly to your media library. All photos are searchable and easy to discover. They are licensed under Creative Commons Zero which means you can copy, modify, distribute and use the photos for free, including commercial purposes.
Version: 1.0.0
Author: Themely
Author URI: https://www.themely.com/
License: GPLv2
*/
include (plugin_dir_path(__FILE__) . 'settings.php');

class Themely_StockPhotos {
	
	function __construct()
		{
		add_action('plugins_loaded', [$this, 'stockphotos_load_textdomain']);
		add_action('admin_enqueue_scripts', [$this, 'stockphotos_enqueue_scripts']);
		add_action('media_upload_stockphotos_tab', [$this, 'media_upload_stockphotos_tab_handler']);
		add_filter('media_upload_tabs', [$this, 'media_upload_tabs_handler']);
		/*		 if ( ! $stockphotos_settings['button'] | $stockphotos_settings['button'] == 'true' ) {
		add_filter( 'media_buttons_context',[$this,'media_buttons_context_handler']);
		}*/
		add_action('admin_init', [$this, 'checkRequest']);
		}

	function media_buttons_context_handler($editor_id = '')
		{
		return '<a href="' . add_query_arg('tab', 'stockphotos_tab', esc_url(get_upload_iframe_src())) . '" id="' . esc_attr($editor_id) . '-add_media" class="thickbox button" title="' . esc_attr__('Stock Photos', 'stockphotos') . '"><span class="dashicons dashicons-images-alt2"></span> Add Stock Photos</a>';
		}

	function stockphotos_load_textdomain()
		{
		load_plugin_textdomain('stockphotos', false, dirname(plugin_basename(__FILE__)) . '/langs/');
		}

	function stockphotos_enqueue_scripts($hook)
		{
		if ($hook == "media-upload-popup")
			{
			$wp_styles = new WP_Styles();
			wp_default_styles($wp_styles);
			wp_enqueue_script('jquery');
			wp_enqueue_style('stockphoto_css', plugins_url('/assets/css/style.css', __FILE__) , array() , '1.0.0');
			wp_enqueue_style('media-views', site_url() + '/wp-includes/css/media-views.min.css', array(
				'buttons',
				'dashicons',
				'wp-mediaelement'
			) , '1.0.0');
			wp_enqueue_script('unsplash', plugins_url('/assets/js/unsplash.min.js', __FILE__) , array() , '1.0.0', true);
			wp_enqueue_script('main', plugins_url('/assets/js/main.js', __FILE__) , array() , '1.0.1', true);
			}
		  else
			{
			wp_enqueue_script('themely-media', plugins_url('/assets/js/media.js', __FILE__) , array() , '1.0.0', true);
			}
		}

	function media_upload_tabs_handler($tabs)
		{
		$tabs['stockphotos_tab'] = __('Import Stock Photos', 'stockphotos');
		return $tabs;
		}

	function themely_stockphotos_tab()
		{
		/* media_upload_header();*/
		$stockphotos_settings = get_option('stockphotos_options'); ?>
		 <div class="stockphotos-form-wrapper">
			 <form id="stockphotos_form" class="stockphotos-form">
			 	<p>Search for high-quality and completely free stock photos and import them directly to your media library. CREDITS: Access to stock photos provided by <a target="_blank" href="https://unsplash.com/">Unsplash</a> and <a target="_blank" href="https://pixabay.com/">Pixabay</a>, plugin developed by <a target="_blank" href="https://www.themely.com/">Themely</a>.</p>
				<p><input id="search" type="text" value="" class="searchStockPhotos" placeholder="Enter search term" style="width:100%;max-width:300px;padding-bottom: 5px;"> <input id="submit_search" class="button button-primary doSearch" type="submit" value="Search">   <a class="button button-secondary settings" href="options-general.php?page=stockphotos_settings" target="_blank">Settings</a></p>
				<p></p>
			 </form>

			 <div>
				 <ul class="tl_stockphotos_tabs">
					<li class="tab active" data-provider="unsplash">Unsplash</li>
					<li class="tab" data-provider="pexels">Pexels</li>
					<li class="tab" data-provider="pixabay">Pixabay</li>
				 </ul>
			 </div>

			 <div id="results-wrapper">

				 <div id="upload-status">
				    <span>
						 <span id="photo-upload-status"></span>
					 </span>
				 </div>

			 <div class="tabResults" id="unsplash">
				<div class="stockPhoto_results">
					<p>Enter a search term</p>
				</div>
				<div style="clear: both;"></div>
				<div class="pager">
					<a href="#" class="button prev">Prev</a>
					<a href="#" class="button next">Next</a>
				</div>
			 </div>

			 <div class="tabResults" id="pexels" style="display:none">
				 <div class="stockPhoto_results">
					<p>Enter a search term</p>
				 </div>
				 <div style="clear: both;"></div>
				 <div class="pager">
					<a href="#" class="button prev">Prev</a>
					<a href="#" class="button next">Next</a>
				</div>
			 </div>

			 <div class="tabResults" id="pixabay" style="display:none">
				 <div class="stockPhoto_results wp-core-ui">
					 <p>Enter a search term</p>
				 </div>
				 <div style="clear: both;"></div>
				 <div class="pager">
					<a href="#" class="button prev">Prev</a>
					<a href="#" class="button next">Next</a>
				</div>
			</div>
			<div class="import">
				<a class="button button-primary" href="#" id="addtogallery" style="display:none">Import Selected Photos</a>
		 	</div>
		 </div>
		 </div>

		 <script>
			 jQuery(function(){

				var uploading = false;

				Themely.StockPhotos.init({
					resultsPerPage: <?php echo $stockphotos_settings["per_page"]?>,
					unsplashApiKey: '9b65df30b3979a934bf91d685b54ed8d332fdecd7d147aa139386ad4eab7d83b',
					pixabayApiKey: '4570352-5103e8262100762475e54e2bd',
					pexelsEnabled: false,
					selectedClickHandler: selectImage,
					disselectedClickHandler: disselectImage
				});

				 jQuery('#addtogallery').click(function(e){

					 if (Themely.StockPhotos.selectedImages().length > 0) {
						 uploadToGallery();
					 }

				 	return false;
				 });


				 //Ref from gallery...

				 function selectImage(e,imageData){

					 var selectedImages = Themely.StockPhotos.selectedImages();
					 jQuery("#addtogallery").show();
				 }

				 function disselectImage(e,imageData){

					 var selectedImages = Themely.StockPhotos.selectedImages();
					 if(selectedImages.length == 0){
						 jQuery("#addtogallery").hide();
					 }
				 }

				 function uploadToGallery(){

					 uploading = true;

				 	Themely.StockPhotos.disableSelection();

					 var selectedImages = Themely.StockPhotos.selectedImages();
					 var uploadCount = 0;
					 var errorCount = 0;
					 var selectedImagesCount = selectedImages.length;

					 jQuery("#upload-status").addClass('overlay');
					 jQuery("#upload-status #photo-upload-status").html('Adding Photo(s) ' + uploadCount + '/' + selectedImagesCount);

					 jQuery.each(selectedImages, function(i){

					 	var imageData = selectedImages[i];



					 jQuery.post('.',
						 { stockPhoto_upload: "1",
							 image_url: imageData.image,
							 author: imageData.author,
							 title: imageData.title,
							 description: imageData.description,
							 image_size: 'large',  //large, medium, thumbnail, full etc... //List WP media sizes?
							 q: imageData.query,
							 wpnonce: '<?= wp_create_nonce('stockPhoto_upload_security_nonce'); ?>' },
						 function(data){

							 uploadCount++;

							if(data.success==true){
								//Successful for one of n images.

								jQuery("#upload-status #photo-upload-status").html('Adding Photo(s) ' + uploadCount + '/' + selectedImagesCount);

							}else{
								errorCount++; //Error on one of the images, timeout?  server?  something else?
							}

							 if(uploadCount == selectedImagesCount){

								 //done regardless of error count....

								 jQuery("#upload-status").removeClass('overlay');
								 Themely.StockPhotos.clearSelectedImages();
								 window.parent.Themely.ReloadMediaLibrary(); //call from parent window



							 }

					 },
					    'json');
					 });

				 }


			 });
		 </script>
		 <?php
	 }

	 function media_upload_stockphotos_tab_handler() {
		 wp_iframe([$this,'themely_stockphotos_tab'] );
	 }


	 function checkRequest(){


		 if ( isset( $_POST['stockPhoto_upload'] ) ) {
			 # "pluggable.php" is required for wp_verify_nonce() and other upload related helpers
			 if ( ! function_exists( 'wp_verify_nonce' ) ) {
				 require_once( ABSPATH . 'wp-includes/pluggable.php' );
			 }

			 $nonce = $_POST['wpnonce'];
			 if ( ! wp_verify_nonce( $nonce, 'stockPhoto_upload_security_nonce' ) ) {
				 die( 'Error: Invalid request.' );
			 }

			 $post_id =0;


			 $args = array('timeout'=> 300000); //5 minutes
			 $url        = str_replace( 'https:', 'http:', $_POST['image_url'] );
			 $response = wp_remote_get( $url, $args);

			 if ( is_wp_error( $response ) ) {
				 echo json_encode(["success"=>false,"message"=>'Error: ' . $response->get_error_message() ]);
				 exit();
			 }

			 $q_tags = explode( ' ', $_POST['q'] );

			 array_splice( $q_tags, 2 );

			 foreach ( $q_tags as $k => $v ) {
				 // remove ../../../..
				 $v            = str_replace( "..", "", $v );
				 $v            = str_replace( "/", "", $v );
				 $q_tags[ $k ] = trim( $v );
			 }

			 $path_info = pathinfo( $url );
			 $randomBytes= bin2hex(random_bytes(4));
			 $ext = array_key_exists('extension',$path_info) ? $path_info['extension'] : "jpg"; //default jpg
			 $file_name = sanitize_file_name( implode( '_', $q_tags ) . '_' . time() . $randomBytes . '.' . $ext );

			 $wp_upload_dir     = wp_upload_dir();
			 $image_upload_path = $wp_upload_dir['path'];

			 if ( ! is_dir( $image_upload_path ) ) {
				 if ( ! @mkdir( $image_upload_path, 0777, true ) ) {
					 echo json_encode(["success"=>false,"message"=>'Error: Failed to create upload folder ' . $image_upload_path ]);
					exit();
				 }
			 }

			 $target_file_name = $image_upload_path . '/' . $file_name;
			 $result           = @file_put_contents( $target_file_name, $response['body'] );
			 unset( $response['body'] );

			 if ( $result === false ) {
				 echo json_encode(["success"=>false,"message"=>'Error: Failed to write file ' . $target_file_name ]);
				 exit;
			 }

			 // are we dealing with an image
			 require_once( ABSPATH . 'wp-admin/includes/image.php' );
			 if ( ! wp_read_image_metadata( $target_file_name ) ) {
				 unlink( $target_file_name );
				 echo json_encode(["success"=>false,"message"=>"Error: File is not an image."]);
				 exit;
			 }

			 $image_title = ucwords( implode( ', ', $q_tags ) );

			 $attachment_caption = '';


			 // insert attachment
			 $wp_filetype = wp_check_filetype( basename( $target_file_name ), null );
			 $attachment  = array(
				 'guid'           => $wp_upload_dir['url'] . '/' . basename( $target_file_name ),
				 'post_mime_type' => $wp_filetype['type'],
				 'post_title'     => preg_replace( '/\.[^.]+$/', '', $image_title ),
				 'post_status'    => 'inherit'
			 );
			 $attach_id   = wp_insert_attachment( $attachment, $target_file_name, $post_id );
			 if ( $attach_id == 0 ) {
				 die( 'Error: File attachment error' );
			 }

			 $attach_data = wp_generate_attachment_metadata( $attach_id, $target_file_name );
			 $result      = wp_update_attachment_metadata( $attach_id, $attach_data );
			 if ( $result === false ) {
				 die( 'Error: File attachment metadata error' . '    '  . $target_file_name);
			 }

			 $size = $_POST["image_size"];
			 $image = wp_get_attachment_image($attach_id,$size);
			 $response= ["success"=>true,"attachmentId" =>  $attach_id,"imageHtml" => $image,"meta"=>$result];

			echo json_encode($response);
			exit;
		 }

	 }

 }

$tlStockPhotos = new Themely_StockPhotos();
?>
