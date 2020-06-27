<?php

require_once 'aliyun-oss-php-sdk-2.3.1/autoload.php';

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * AliyunOSS储存Typecho上传附件.
 * 
 * @package AliOssForTypecho 
 * @author droomo.
 * @version 1.1.8
 * @link https://www.droomo.top/
 */

class AliOssForTypecho_Plugin extends Typecho_Widget implements Typecho_Plugin_Interface {

    const UPLOAD_DIR = 'usr/uploads/';//上传文件目录，相对网站根目录
    const LOG_SUFFIX = '__oss-plugin-log/';//日志目录，放置于UPLOAD_DIR下

    public function api_version() {
        echo __FUNCTION__;
        var_dump (self::get_plugin_information()['version']);
    }
    public function api_log() {
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        } else {
        }
    }

    public static function get_plugin_information() {
        Typecho_Widget::widget('Widget_Plugins_List@activated', 'activated=1')->to($activatedPlugins);
        $activatedPlugins = json_decode(json_encode($activatedPlugins),true);
        $plugins_list = $activatedPlugins['stack'];
        $plugins_info = array();
        for ($i = 0; $i < count($plugins_list); $i++){
            if($plugins_list[$i]['title'] == 'AliOssForTypecho'){
                $plugins_info = $plugins_list[$i];
                break;
            }
        }
        if (count($plugins_info) < 1) {
            return false;
        }
        return $plugins_info;
    }
    

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle         = array('AliOssForTypecho_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle         = array('AliOssForTypecho_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle         = array('AliOssForTypecho_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle     = array('AliOssForTypecho_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('AliOssForTypecho_Plugin', 'attachmentDataHandle');

        Helper::addRoute('__alioss_for_tp_plugin_version__', '/__alioss_for_tp_plugin_api__/version', 'AliOssForTypecho_Plugin', 'api_version');
        Helper::addRoute('__alioss_for_tp_plugin_log__', '/__alioss_for_tp_plugin_api__/log', 'AliOssForTypecho_Plugin', 'api_log');

        return _t('启用成功，请进行相应设置！');
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {
        Helper::removeRoute('__alioss_for_tp_plugin_version__');
        Helper::removeRoute('__alioss_for_tp_plugin_log__');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form) {
        $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        
        $log_file_name = $upload_root . self::LOG_SUFFIX . 'error.log';
        
        if (is_writable($upload_root)) {
            $log_content = '恭喜！暂无错误日志产生，请继续保持维护～';
            $log_color = '#009900';

            if (!file_exists($log_file_name)) {
                self::makeUploadDir($upload_root . self::LOG_SUFFIX);
                fopen($log_file_name, 'w');
                if (!file_exists($log_file_name)) {
                    $log_content = '无法创建日志文件，请检查权限设置！！！开启SELinux的用户注意合理配置权限！';
                    $log_color = '#f00000';
                }
            } else {
                try {
                    $content = file_get_contents($log_file_name);
                    if ($content) {
                        $log_content = $content;
                        $log_color = '#dd0000';
                    }
                } catch (Exception $e) {
                    $log_content = '注意！无法读取日志文件，请检查文件状态！';
                    $log_color = '#f00000';
                }
            }
        } else {
            $log_content = '！！！注意！！！ 
当前网站上传目录无写入权限，无法记录日志！
请给路径 '.$upload_root.' 赋予写入权限。开启SELinux的用户注意合理配置权限。';
            $log_color = '#f00000';
        }
?>
<div>
<h3>插件使用说明</h3>
<ol>
<li>插件基于<a href="https://github.com/aliyun/aliyun-oss-php-sdk/releases/tag/v2.3.1">aliyun-oss-php-sdk Release 2.3.1</a>开发，
若以后SDK开发包更新导致插件不可用，请到 <a target="_blank" href="https://www.droomo.top/AliOssForTypecho.html">我的博客^-^</a>获取新版本插件，如果我还用typecho、阿里云OSS就会更新。<br/></li>
<li>为保证正确记录日志，请赋予以下目录写权限：<code style="color:#333;font-size:12px;"><?php echo $upload_root;?></code>，并定期查阅日志处理事件错误。开启SELinux的用户注意合理配置权限。</li>
<li>当文件成功上传到OSS，但保存到服务器失败时，总体进度会显示失败。在OSS中的文件不会自动删除，请根据错误日志自行处理。</li>
<li>运行在云应用引擎上的站点“在服务器保留备份”选项无效，且无法记录日志。</li>
<li>旧版本Typecho存在无法上传大写扩展名文件的bug，请更新Typecho程序。<br/></li>
<li>如有问题或建议请到 <a target="_blank" href="https://www.droomo.top/AliOssForTypecho.html">我的博客https://www.droomo.top/AliOssForTypecho.html</a> 留言</li>
</ol>
<p>以下是本插件产生的错误日志，请定期查看并处理：</p>
<p>日志文件是&nbsp;&nbsp;<span style="color:#666;font-sieze:8px"><?php echo $log_file_name;?><span></p>
<div style="width:98%;margin: 0 auto">
<textarea style="color:<?php echo $log_color;?>;height:160px;width:100%;"><?php echo $log_content;?></textarea></div>
</div>
<?php
        $buketName = new Typecho_Widget_Helper_Form_Element_Text('bucketName', NULL, null,
            _t('Bucket名称'), _t('请填写Buket名称'));
        $form->addInput($buketName->addRule('required', _t('必须填写Bucket名称')));

        $accessKeyId = new Typecho_Widget_Helper_Form_Element_Text('accessKeyId', NULL, null,
            _t('ACCESS KEY ID'), _t('请填写ACCESS KEY ID'));
        $form->addInput($accessKeyId->addRule('required', _t('必须填写ACCESS KEY ID')));

        $accessKeySecret = new Typecho_Widget_Helper_Form_Element_Text('accessKeySecret', NULL, null,
            _t('ACCESS KEY SECRET'), _t('请填写请填写ACCESS KEY SECRET'));
        $form->addInput($accessKeySecret->addRule('required', _t('必须填写ACCESS_KEY')));

        $endPoint = new Typecho_Widget_Helper_Form_Element_Select('endPoint', 
            array(
                "oss-cn-hangzhou"   =>  "华东1（杭州）oss-cn-hangzhou",
                "oss-cn-shanghai"   =>  "华东2（上海）oss-cn-shanghai",
                "oss-cn-qingdao"    =>  "华北1（青岛）oss-cn-qingdao",
                "oss-cn-beijing"    =>  "华北2（北京）oss-cn-beijing",
                "oss-cn-zhangjiakou"    =>  "华北3（张家口）oss-cn-zhangjiakou",
                "oss-cn-huhehaote"  =>  "华北5（呼和浩特）oss-cn-huhehaote",
                "oss-cn-wulanchabu" =>  "华北6（乌兰察布）oss-cn-wulanchabu",
                "oss-cn-shenzhen"   =>  "华南1（深圳）oss-cn-shenzhen",
                "oss-cn-heyuan"     =>  "华南2（河源）oss-cn-heyuan",
                "oss-cn-chengdu"    =>  "西南1（成都）oss-cn-chengdu",
                "oss-cn-hongkong"   =>  "中国（香港）oss-cn-hongkong",
                "oss-us-west-1"     =>  "美国西部1（硅谷）oss-us-west-1",
                "oss-us-east-1"     =>  "美国东部1（弗吉尼亚）oss-us-east-1",
                "oss-ap-southeast-1"    =>"亚太东南1（新加坡）oss-ap-southeast-1",
                "oss-ap-southeast-2"    =>"亚太东南2（悉尼）oss-ap-southeast-2",
                "oss-ap-southeast-3"    =>"亚太东南3（吉隆坡）oss-ap-southeast-3",
                "oss-ap-southeast-5"    =>"亚太东南5（雅加达）oss-ap-southeast-5",
                "oss-ap-northeast-1"    =>"亚太东北1（日本）oss-ap-northeast-1",
                "oss-ap-south-1"    =>  "亚太南部1（孟买）oss-ap-south-1",
                "oss-eu-central-1"  =>  "欧洲中部1（法兰克福）oss-eu-central-1",
                "oss-eu-west-1"     =>  "英国（伦敦）oss-eu-west-1",
                "oss-me-east-1"     =>  "中东东部1（迪拜）oss-me-east-1",
                "other"             => '自定义'
            ),
            'oss-cn-qingdao',
            _t('区域选择（若区域不在列表中则选择自定义，然后填写区域）'), '');
        $form->addInput($endPoint);

        $endPointType = new Typecho_Widget_Helper_Form_Element_Select('endPointType',
            array(
                ".aliyuncs.com"             => '外网',
                "-internal.aliyuncs.com"    => '内网',
            ),
            '.aliyuncs.com', '<label class="AliossForTypecho-mark-other-endpoint-hide">选择服务器与OSS连接方式</label>',
            '<span class="AliossForTypecho-mark-other-endpoint-hide">在你了解两种连接方式的不同作用的情况下修改此选项</span>');
        $form->addInput($endPointType);
        
        $otherEndPoint = new Typecho_Widget_Helper_Form_Element_Text('otherEndPoint', NULL, '',
            '<label class="AliossForTypecho-mark-other-endpoint-show">自定义EndPoint</label>', '<span class="AliossForTypecho-mark-other-endpoint-show">
            填写全部Endpoint地址，通常以\'.aliyuncs.com\'或\'-internal.aliyuncs.com\'结尾。开头不包含http://，结尾不包含"/"。<br/>例如"oss-cn-qingdao.aliyuncs.com"</span>');
        $form->addInput($otherEndPoint);
        
        $userDir = new Typecho_Widget_Helper_Form_Element_Text('userDir', NULL, 'typecho/',
            _t('要储存的路径'), _t('请填写文件储存的路径（相对OSS根目录），以字母或数字开头，以"/"结尾。留空则上传到根目录。'));
        $form->addInput($userDir);
        
        $cdnUrl = new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', NULL, '',
            _t('自定义（CDN）域名'), '请填写自定义域名，留空则使用外网Endpoint访问，以http://或https://开头，以"/"结尾');
        $form->addInput($cdnUrl);
        
        $diy_style = new Typecho_Widget_Helper_Form_Element_Text('des', NULL, '', _t('默认自定义样式'), 
        _t('通过后缀的方式使用自定义样式，留空为不使用。使用详情见<a target="_blank" href="https://help.aliyun.com/document_detail/48884.html">阿里云文档</a>'));
        $form->addInput($diy_style);
        
        $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('ifLoaclSave', array( "1" => '保留', "0" => '不保留' ), "1",
        _t('在服务器保留备份'), _t('是否在服务器保留备份')));
?>
<script>
window.onload = function() {
    (function() {
        var AliossForTypecho_otherSelected = document.getElementsByName("endPoint")[0].value === "other";
        var AliossForTypecho_otherEndpointShowingTags = document.getElementsByClassName("AliossForTypecho-mark-other-endpoint-show");
        var AliossForTypecho_otherEndpointHiddingTags = document.getElementsByClassName("AliossForTypecho-mark-other-endpoint-hide");
        var AliossForTypecho_otherEndPointInputTag = document.getElementsByName("otherEndPoint")[0];
        var AliossForTypecho_endPointTypeInputTag = document.getElementsByName("endPointType")[0];
        var AliossForTypecho_loadLabels = function () {
            var AliossForTypecho_s1 = null, AliossForTypecho_s2 = null;
            if (AliossForTypecho_otherSelected) {
                AliossForTypecho_s1 = "none";
                AliossForTypecho_s2 = "block";
                AliossForTypecho_otherEndPointInputTag.type = "text";
            } else {
                AliossForTypecho_s2 = "none";
                AliossForTypecho_s1 = "block";
                AliossForTypecho_otherEndPointInputTag.type = "hidden";
                AliossForTypecho_endPointTypeInputTag.type = "";
            }
            AliossForTypecho_endPointTypeInputTag.style.display = AliossForTypecho_s1;
            for (var AliossForTypecho_i = 0; AliossForTypecho_i < AliossForTypecho_otherEndpointShowingTags.length; AliossForTypecho_i++) {
                AliossForTypecho_otherEndpointShowingTags[AliossForTypecho_i].style.display = AliossForTypecho_s2;
            }
            for (var AliossForTypecho_i = 0; AliossForTypecho_i < AliossForTypecho_otherEndpointHiddingTags.length; AliossForTypecho_i++) {
                AliossForTypecho_otherEndpointHiddingTags[AliossForTypecho_i].style.display = AliossForTypecho_s1;
            }
        };
        document.getElementsByName("endPoint")[0].onchange = function(e) {
            AliossForTypecho_otherSelected = e.target.value === "other";
            AliossForTypecho_loadLabels();
        };
        AliossForTypecho_loadLabels();
    })();
}
</script>
<?php
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}
        
    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file) {
        if (empty($file['name'])) {
            return FALSE;
        }
        $ext = self::getExtentionName($file['name']);
        if (!self::checkFileType($ext)) {
            return FALSE;
        }
        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return FALSE;
        }

        $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        
        $options = Typecho_Widget::widget('Widget_Options');
        $user_dir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = 'http://' . (($options->plugin('AliOssForTypecho')->endPoint === "other") ?
                $options->plugin('AliOssForTypecho')->otherEndPoint :
                $options->plugin('AliOssForTypecho')->endPoint . $options->plugin('AliOssForTypecho')->endPointType);
        $access_id   = $options->plugin('AliOssForTypecho')->accessKeyId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKeySecret;

        try {
            $oss_client = new OssClient($access_id, $access_key, $end_point);
            $oss_client->doesBucketExist($bucket_name);
        } catch (Exception $e) {
            $error = '错误：连接OSS Client实例失败' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }

        $save_on_server = $options->plugin('AliOssForTypecho')->ifLoaclSave;

        $file_origin_name = self::getSafeName($file['name']);
        $relative_path = date('Y/m/d/');
        
        $remote_file_name = $user_dir . $relative_path . $file_origin_name;

        if ($save_on_server === "1" && !Typecho_Common::isAppEngine()) {

            $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
            
            $local_file_name = $upload_root . $relative_path . $file_origin_name;
            try{
                $exist_on_oss = $oss_client->doesObjectExist($bucket_name, $remote_file_name);
                $exist_on_server = file_exists($local_file_name);
            } catch(OssException $e) {
                $error = '错误：检查OSS或本地服务器中中是否存在同名文件时失败' . "\r\n" .
                        '错误描述：' . $e->getMessage() . "\r\n" .
                        '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                self::my_error_log($error);
                return false;
            }
            
            if ($exist_on_oss || $exist_on_server) {
                // find a name neither exist on oss nor the server
                $pathinfo = pathinfo($file_origin_name);
                for ($i = 1;; $i++) {
                    $file_origin_name = $pathinfo['filename'] . '(' . strval($i) . ').' . self::getExtentionName($file_origin_name);
                    $remote_file_name = $user_dir . $relative_path . $file_origin_name;
                    $local_file_name = $upload_root . $relative_path . $file_origin_name;
                    
                    try{
                        $exist_on_oss = $oss_client->doesObjectExist($bucket_name, $remote_file_name);
                        $exist_on_server = file_exists($local_file_name);
                    } catch(OssException $e) {
                        $error = '错误：检查OSS或本地服务器中中是否存在同名文件时失败' . "\r\n" .
                                '错误描述：' . $e->getMessage() . "\r\n" .
                                '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        self::my_error_log($error);
                        return false;
                    }

                    if ($exist_on_oss || $exist_on_server) {
                    } else {
                        break;
                    }
                }
            }
        } else {
            try{
                $exist_on_oss = $oss_client->doesObjectExist($bucket_name, $remote_file_name);
            } catch(OssException $e) {
                $error = '错误：检查OSS中是否存在同名文件时失败' . "\r\n" .
                                '错误描述：' . $e->getMessage() . "\r\n" .
                                '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                self::my_error_log($error);
                return false;
            }
            if ($exist_on_oss || $exist_on_server) {
                // find a name not exist on oss
                $pathinfo = pathinfo($file_origin_name);
                for ($i = 1;; $i++) {
                    $file_origin_name = $pathinfo['filename'] . '(' . strval($i) . ').' . self::getExtentionName($file_origin_name);
                    $remote_file_name = $user_dir . $relative_path . $file_origin_name;
                    
                    try{
                        $exist_on_oss = $oss_client->doesObjectExist($bucket_name, $remote_file_name);
                    } catch(OssException $e) {
                        $error = '错误：检查OSS中是否存在同名文件时失败' . "\r\n" .
                                '错误描述：' . $e->getMessage() . "\r\n" .
                                '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        self::my_error_log($error);
                        return false;
                    }

                    if (!$exist_on_oss) {
                        break;
                    }
                }
            }
        }

        try{
            $ali_response = $oss_client->putObject($bucket_name, $remote_file_name, $content);
        } catch(OssException $e) {
            $error = '错误：将文件储存到OSS失败' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }

        if (200 != $ali_response['info']['http_code']) {
            $error = '错误：将文件储存到OSS时返回码不正常' . "\r\n" .
                    '错误码：' . $ali_response['info']['http_code'] . "\r\n" .
                    '远程文件：' . $remote_file_name . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        } else {
            if ($save_on_server === "1" && !Typecho_Common::isAppEngine()) {
                $file_dir_name = dirname($local_file_name);
                $dir_exist = true;

                if (!is_dir($file_dir_name) && !self::makeUploadDir($file_dir_name)) {
                    $dir_exist = false;
                }

                if ($dir_exist) {
                    if (!file_put_contents($local_file_name, $content)) {
                        $error = '错误：文件已保存到OSS，将文件储存到本地服务器时失败，请手动删除OSS上的文件，开启SELinux的用户注意合理配置权限。' . "\r\n" .
                             '文件路径：' . $local_file_name . "\r\n" .
                             '远程文件：' . $remote_file_name . "\r\n" .
                             '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        self::my_error_log($error);
                        return false;
                    }
                } else {
                    $error = '错误：文件已保存到OSS，将文件储存到本地服务器时创建目录失败，请检查服务器权限设置，开启SELinux的用户注意合理配置权限。' . "\r\n" .
                             '无法创建路径：' . $file_dir_name . "\r\n" .
                             '远程文件：' . $remote_file_name . "\r\n" .
                             '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    self::my_error_log($error);
                    return false;
                }
            }

            return array(
                'name' => $file_origin_name,
                'path' => $relative_path . $file_origin_name,
                'size' => intval($ali_response['oss-requestheaders']['Content-Length']),
                'type' => $ext,
                'mime' => $ali_response['oss-requestheaders']['Content-Type']
            );
            
        }
    }

    /**
     * 修改文件处理函数
     *
     * @access public
     * @param array $content 老文件
     * @param array $file 新上传的文件
     * @return mixed
     */
    public static function modifyHandle($content, $file)
    {
        if (empty($file['name'])) {
            return false;
        }
        $ext = self::getExtentionName($file['name']);
        if ($content['attachment']->type != $ext) {
            return false;
        }
        if (isset($file['tmp_name'])) {
            $new_file_content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $new_file_content = $file['bytes'];
        } else {
            return false;
        }

        $options = Typecho_Widget::widget('Widget_Options');
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = 'http://' . (($options->plugin('AliOssForTypecho')->endPoint === "other") ?
                $options->plugin('AliOssForTypecho')->otherEndPoint :
                $options->plugin('AliOssForTypecho')->endPoint . $options->plugin('AliOssForTypecho')->endPointType);
        $access_id   = $options->plugin('AliOssForTypecho')->accessKeyId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKeySecret;
         
        $path = $content['attachment']->path;

        $remote_file_name = $userDir . $path;
        
        $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
        $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                    defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
        $local_file_name = $upload_root . $path;

        if ($ifLoaclSave && (!is_writable($upload_root) || !is_writable($local_file_name))) {
            $error = '错误：修改文件失败，旧文件无写权限，开启SELinux的用户注意合理配置权限。' . "\r\n" .
                            '本地文件：' . $local_file_name . "\r\n" .
                            '远程文件：' . $remote_file_name . "\r\n" .
                            '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }

        try {
            $oss_client = new OssClient($access_id, $access_key, $end_point);
            $oss_client->doesBucketExist($bucket_name);
        } catch (Exception $e) {
            $error = '错误：连接OSS Client实例失败' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }

        try{
            $ali_response = $oss_client->putObject($bucket_name, $remote_file_name, $new_file_content);
        } catch(OssException $e) {
            $error = '错误：将文件储存到OSS时失败' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }
        
        if (200 != $ali_response['info']['http_code']) {
            $error = '错误：将文件储存到OSS时返回码不正常' . "\r\n" .
                    '错误码：' . $ali_response['info']['http_code'] . "\r\n" .
                    '远程文件：' . $remote_file_name . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        } else {                         
            if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) {
                if (file_exists($local_file_name) && !unlink($local_file_name)) {
                    $error = '错误：修改文件失败，无法删除旧文件' . "\r\n" .
                                '本地文件：' . $local_file_name . "\r\n" .
                                '远程文件：' . $remote_file_name . "\r\n" .
                                '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    self::my_error_log($error);
                    return false;
                }

                $file_dir_name = dirname($local_file_name);
                $dir_exist = true;

                if (!is_dir($file_dir_name) && !self::makeUploadDir($file_dir_name)) {
                    $dir_exist = false;
                }

                if ($dir_exist) {
                    if (!file_put_contents($local_file_name, $new_file_content)) {
                        $error = '错误：文件已保存到OSS，将文件储存到本地服务器时失败，请手动删除OSS上的文件' . "\r\n" .
                            '文件路径：' . $local_file_name . "\r\n" .
                            '远程文件：' . $remote_file_name . "\r\n" .
                            '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    self::my_error_log($error);
                    return false;
                    }
                } else {
                    $error = '错误：文件已保存到OSS，将文件储存到本地服务器时创建目录失败，请检查服务器权限设置，请手动删除OSS上的文件' . "\r\n" .
                             '无法创建路径：' . $file_dir_name . "\r\n" .
                             '远程文件：' . $remote_file_name . "\r\n" .
                             '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    self::my_error_log($error);
                    return false;
                }
            }
        }

        return array(
            'name' => $content['attachment']->name,
            'path' => $path,
            'size' => intval($ali_response['oss-requestheaders']['Content-Length']),
            'type' => $ext,
            'mime' => $ali_response['oss-requestheaders']['Content-Type']
        );
    }

    /**
     * 删除文件
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function deleteHandle(array $content)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = 'http://' . (($options->plugin('AliOssForTypecho')->endPoint === "other") ?
                        $options->plugin('AliOssForTypecho')->otherEndPoint :
                        $options->plugin('AliOssForTypecho')->endPoint . $options->plugin('AliOssForTypecho')->endPointType);
        $access_id   = $options->plugin('AliOssForTypecho')->accessKeyId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKeySecret;
        $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;

        $path = $content['attachment']->path;
        $object_name = $userDir . $path;
        try {
            $oss_client = new OssClient($access_id, $access_key, $end_point);
            $oss_client->doesBucketExist($bucket_name);
        } catch (Exception $e) {
            $error = '错误：删除文件失败，无法连接OSS Client实例' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    'OSS文件:' . $object_name . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
            return false;
        }

        try {
            $ali_response = $oss_client->deleteObject($bucket_name, $object_name);
        } catch (Exception $e) {
            $error = '错误：删除OSS文件失败' . "\r\n" .
                    '错误描述：' . $e->getMessage() . "\r\n" .
                    'OSS文件： ' . $object_name . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
            self::my_error_log($error);
        }

        $delete_local_succeed = true;
        if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) {
            $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                                defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
            $local_file_name = $upload_root . $path;
            
            $delete_local_succeed = false;
            if (file_exists($local_file_name)) {
                if (!is_writable($local_file_name)) {
                    $error = '错误：删除本地文件失败，请检查权限设置' . "\r\n" .
                    '文件路径：' . $local_file_name . "\r\n" .
                    '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    self::my_error_log($error);
                } else {
                    try {
                        $delete_local_succeed = unlink($local_file_name);    
                    } catch (Exception $e) {
                        $error = '错误：删除本地文件失败' . "\r\n" .
                        '错误描述：' . $e->getMessage() . "\r\n" .
                        '文件路径：' . $local_file_name . "\r\n" .
                        '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        self::my_error_log($error);
                    }
                }
            } else {
                $delete_local_succeed = true;
            }
        }
        return $delete_local_succeed && ($ali_response['info']['http_code'] === 204);
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content) {
        $options    = Typecho_Widget::widget('Widget_Options');
        
        $cdnUrl     = $options->plugin('AliOssForTypecho')->cdnUrl;
        $userDir    = $options->plugin('AliOssForTypecho')->userDir;
        $diy_style  = $options->plugin('AliOssForTypecho')->des;
        if (empty($cdnUrl)) {
            $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
            $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                            $options->plugin('AliOssForTypecho')->otherEndPoint : 
                            $options->plugin('AliOssForTypecho')->endPoint;
            return 'https://' . $bucket_name . '.' . $end_point . '.aliyuncs.com/' . $userDir . $content['attachment']->path . $diy_style;
        } else {
            return $cdnUrl . $userDir . $content['attachment']->path . $diy_style;
        }
    }

    /**
     * 获取实际文件数据
     *
     * @access public
     * @param array $content
     * @return string
     */
    public static function attachmentDataHandle(array $content)
    {
        return file_get_contents(self::attachmentHandle($content));
    }

    /**
     * 检查文件名
     *
     * @access private
     * @param string $ext 扩展名
     * @return boolean
     */
    private static function checkFileType($ext)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        return in_array($ext, $options->allowedAttachmentTypes);
    }
    
    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    private static function makeUploadDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeUploadDir($path);
    }

    /**
     * 获取安全的文件名 
     * 
     * @param string $name 
     * @static
     * @access private
     * @return string
     */
    private static function getSafeName(&$name) {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
        return $name;
    }

    private static function my_error_log(&$error) {
        if (!Typecho_Common::isAppEngine()) {
            $upload_root = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);
            $error_log_file = $upload_root . self::LOG_SUFFIX . 'error.log';

            $log_dir = dirname($error_log_file);
            if (!is_dir($log_dir)) {
                if (is_writeable($upload_root)) {
                    self::makeUploadDir($log_dir);
                }
            }
            if (is_writeable($log_dir)) {
                error_log($error, 3, $error_log_file);
            }   
        }
    }

    private static function getExtentionName(&$name) {
        $info = pathinfo($name);
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }

}
