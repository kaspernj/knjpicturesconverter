<?php
	class WinMain{
		private $glade;
		private $formats;
		
		function __construct(){
			require_once("knjphpframework/functions_knj_msgbox.php");
			require_once("knjphpframework/functions_knj_picture.php");
			
			$this->glade = new GladeXML("glade/win_main.glade");
			$this->glade->signal_autoconnect_instance($this);
			
			$this->formats = array(
				0 => "JPEG",
				1 => "PNG",
				2 => "GIF"
			);
			foreach($this->formats AS $value){
				$this->glade->get_widget("cbFormat")->append_text($value);
			}
			$this->glade->get_widget("cbFormat")->set_active(0);
			
			$this->glade->get_widget("window")->show();
		}
		
		function on_btnStart_clicked(){
			$dir_from = utf8_encode($this->glade->get_widget("fcbFrom")->get_filename());
			$dir_to = utf8_encode($this->glade->get_widget("fcbTo")->get_filename());
			
			$format = strtolower($this->formats[$this->glade->get_widget("cbFormat")->get_active()]);
			$size = $this->glade->get_widget("txtSize")->get_text();
			$quality = $this->glade->get_widget("txtQuality")->get_text();
			
			if (!is_dir($dir_from) || !is_dir($dir_to)){
				msgbox(gtext("Warning"), gtext("Please choose valid folders first."), "warning");
				return null;
			}
			
			if (!is_numeric($size) || $size <= 0 || $size >= 5000){
				msgbox(gtext("Warning"), gtext("Please enter a valid number in the size textbox."), "warning");
				return null;
			}
			
			if (!is_numeric($quality) || $quality <= 0 || $quality > 100){
				msgbox(gtext("Warning"), gtext("Please enter a valid quality."), "warning");
				return null;
			}
			
			$files_total_count = 0;
			$od = opendir($dir_from);
			if ($od){
				while(($file = readdir($od)) !== false){
					if ($file != "." && $file != ".." && substr($file, 0, 1) != "."){
						$files_total_count++;
					}
				}
			}
			
			require_once("knjphpframework/win_status.php");
			$win_status = new WinStatus(array("with_cancelbtn" => true));
			
			$files_count = 0;
			$od = opendir($dir_from);
			if ($od){
				while(($file = readdir($od)) !== false){
					if ($win_status->canceled == true){
						msgbox(gtext("Information"), gtext("Canceled!"), "info");
						break;
					}
					
					if ($file != "." && $file != ".." && substr($file, 0, 1) != "."){
						$files_count++;
						$perc = $files_count / $files_total_count;
						$resize = false;
						
						$win_status->setStatus($perc, sprintf(gtext("Converting pictures... (%s)"), $files_count), true);
						
						try{
							$fn = $dir_from . "/" . $file;
							
							if (is_file($fn)){
								$img_size = GetImageSize($fn);
								
								if ($img_size){
									$img_orig = picture_openrandomformat($fn, $img_size["mime"]);
									
									if ($img_size[0] > $size){
										$width = $size;
										$height = round($img_size[1] / ($img_size[0] / $width));
										$resize = true;
									}elseif($img_size[1] > $size){
										$height = $size;
										$width = round($img_size[0] / ($img_size[1] / $height));
										$resize = true;
									}
									
									if ($resize == true){
										$img_write = ImageCreateTrueColor($width, $height);
										ImageCopyResampled($img_write, $img_orig, 0, 0, 0, 0, $width, $height, $img_size[0], $img_size[1]);
									}else{
										$img_write = $img_orig;
									}
									
									if ($img_orig){
										$pathinfo = pathinfo($file);
										$out_fn = $dir_to . "/" . $pathinfo["filename"];
										
										if ($format == "jpeg"){
											ImageJPEG($img_write, $out_fn . ".jpg", $quality);
										}elseif($format == "png"){
											ImagePNG($img_write, $out_fn . ".png", round($quality / 11, 0));
										}elseif($format == "gif"){
											ImageGIF($img_write, $out_fn . ".gif", $quality);
										}else{
											throw new Exception(sprintf(gtext("Unknown format: \"%s\"."), $format));
										}
										
										if ($resize == true){
											ImageDestroy($img_write);
											ImageDestroy($img_orig);
										}else{
											ImageDestroy($img_orig);
										}
									}
								}
							}
						}catch(Exception $e){
							msgbox(gtext("Warning"), $e->getMessage(), "warning");
						}
					}
				}
			}
			
			$win_status->closeWindow();
		}
		
		function closeWindow(){
			Gtk::main_quit();
		}
	}
?>