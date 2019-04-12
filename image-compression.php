<?php
/*
Plugin Name: Image Compression
Description: メディア追加時に,画像を圧縮(jpeg, png)する。
Author: Kohei Shibata
Version: 1.0
License GPL2

  Copyright 2019 Kohei Shibata

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$set_option_hook = new SetOptionHook();
$image_compression = new ImageCompression();
$option_page = new OptionPage();

class ImageCompression{
	function __construct(){
		require_once(ABSPATH.'wp-admin/includes/image.php');
		add_filter("wp_generate_attachment_metadata", array($this ,"img_compression"), 10 , 2);
	}
	function img_compression($metadata, $attachement_id){
		$fileType = get_post_mime_type($attachement_id);
		$filePath = get_attached_file($attachement_id);

		if(preg_match("/image*/", $fileType)){
			$created_img = $this->create_image($filePath);
			$compress_img = $this->save_image($created_img,$filePath);
			imagedestroy($created_img);

			foreach($metadata['sizes'] as $sub_metadata) {
				$sub_filePath = dirname($filePath).'/'.$sub_metadata['file'];
				$sub_createdImg = $this->create_image($sub_filePath);
				$sub_compressImg = $this->save_image($sub_createdImg, $sub_filePath);
				imagedestroy($sub_createdImg);
			}
		}

		return $metadata ;
	}
	protected function create_image($filePath){
		$imgType = wp_check_filetype(basename($filePath), null);

		if(!strcmp($imgType['type'], "image/jpeg")){
			$created_img = imagecreatefromjpeg($filePath);
		}elseif(!strcmp($imgType['type'], "image/png")) {
			$created_img = imagecreatefrompng($filePath);
		}elseif(!strcmp($imgType['type'], "image/gif")) {
			$created_img = imagecreatefromgif($filePath);
		} else {
			$created_img = false;
		}

		return $created_img;
	}
	protected function save_image($image, $to){
		$imgType = wp_check_filetype(basename($to), null);

		if(!strcmp($imgType['type'], "image/jpeg")){
			imagejpeg($image, $to, get_option('jpeg_quality'));
		}elseif(!strcmp($imgType['type'], "image/png")){
			imagepng($image, $to, get_option('png_quality'));
		}elseif(!strcmp($imgType['type'], "image/gif")){
			imagegif($image, $to);
		}else{
			return false;
		}
	}
}
class OptionPage {
    function __construct() {
        add_action('admin_menu', array($this, 'menu'));
		add_action('admin_init', array($this, 'register_mysettings'));
    }
    function menu() {
        add_options_page(
            'Image Compression',
            'Image Compression',
            'administrator',
            'ic-menu',
            array($this, 'settingPage')
        );
    }
	function register_mysettings() {
		register_setting('baw-settings-group', 'jpeg_quality');
		register_setting('baw-settings-group', 'png_quality');
	}
    function settingPage() {
    ?>
	<div class="wrap">
	<h2>Image Compression 設定</h2>
	<form method="post" action="options.php"> 
		<?php
			settings_fields('baw-settings-group');
			do_settings_sections('baw-settings-group');
		?>
		<div>
			<p>jpeg画像(0:低品質, 100:高品質)</p>
			<input type="range" name="jpeg_quality" value="<?= get_option('jpeg_quality'); ?>" min="0" max="100" step="1" oninput="document.getElementById('display1').value=this.value">
			<output id="display1"><?= get_option('jpeg_quality'); ?></output>
		</div>
		<div>
			<p>png画像(0:無圧縮, 9:最大圧縮)</p>
			<input type="range" name="png_quality" value="<?= get_option('png_quality'); ?>" min="0" max="9" step="1" oninput="document.getElementById('display2').value=this.value">
			<output id="display2"><?= get_option('png_quality'); ?></output>
		</div>
		<?php submit_button(); ?>
	</form>
	</div>
    <?php
    }
}
class SetOptionHook {
	public function __construct() {
		register_activation_hook(__FILE__, array($this,'installed_option'));
		register_uninstall_hook(__FILE__, array($this, 'uninstalled_option'));
	}
	public function installed_option() {
		if(!get_option('image_compression_installed')){
			add_option('image_compression_installed', 1);
			add_option('jpeg_quality', 85);
			add_option('png_quality', 6);
		}
	}
	public function uninstalled_option() {
		if(get_option('image_compression_installed')){
			delete_option('image_compression_installed');
			delete_option('jpeg_quality');
			delete_option('png_quality');
		}
	}
}
