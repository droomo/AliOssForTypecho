<?php

require_once 'aliyun-oss-php-sdk-2.3.0/autoload.php';

use OSS\OssClient;
use OSS\Core\OssException;

/**
 * AliyunOSS储存Typecho上传附件.
 * 
 * @package AliOssForTypecho 
 * @author droomo.
 * @version 1.1.1
 * @link https://www.droomo.top/
 */
class AliOssForTypecho_Plugin implements Typecho_Plugin_Interface
{
    const log_path = 'usr/plugins/AliOssForTypecho/logs/';//错误日志路径，相对网站根目录
    const UPLOAD_DIR = 'usr/uploads/';//上传文件目录

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle         = array('AliOssForTypecho_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle         = array('AliOssForTypecho_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle         = array('AliOssForTypecho_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle     = array('AliOssForTypecho_Plugin', 'attachmentHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = array('AliOssForTypecho_Plugin', 'attachmentDataHandle');

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
    public static function deactivate(){}
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {

        $upload_dir = $localPath = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
            defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);

        $des = new Typecho_Widget_Helper_Form_Element_Text('des', NULL, '', _t('插件使用说明'),
            '<ol>
<li>插件更新于2018-06-05</li>
<li>插件基于<a href="https://github.com/aliyun/aliyun-oss-php-sdk/releases/tag/v2.3.0">aliyun-oss-php-sdk Release 2.3.0</a>开发，
若以后SDK开发包更新导致插件不可用，请到 <a target="_blank" href="https://www.droomo.top/AliOssForTypecho.html">我的博客</a> ^ - ^获取新版本插件，
如果我还用typecho还用阿里云就会更新。<br/></li>
<li>请赋予目录<p style="margin: 0;padding:0">'.$upload_dir.'</p><p style="margin: 0;padding:0">'. __TYPECHO_ROOT_DIR__ .'/'. self::log_path . '</p>写权限，否则可能导致上传失败。</li>
<li>若开启“在服务器保留备份”功能：<br>
成功保存文件到OSS但没有成功保存到服务器的情况下插件不会报错，
<font color="red">这将导致当前文件在服务器上没有备份</font>，但是会在' . __TYPECHO_ROOT_DIR__ .'/'. self::log_path . '目录下生成错误日志"error.log"，请定期查阅并清理。<br/></li>
<li>运行在云应用引擎上的站点“在服务器保留备份”选项无效。<br/></li>
<li>旧版本Typecho存在无法上传大写扩展名文件的bug，请更新Typecho程序。<br/></li>
<li>如有问题或建议请到 <a target="_blank" href="https://www.droomo.top/AliOssForTypecho.html">我的博客</a> 留言</li>
</ol>');
        $form->addInput($des);
        
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
                "oss-cn-hangzhou"      => '华东 1 oss-cn-hangzhou',
                "oss-cn-shanghai"      => '华东 2 oss-cn-shanghai',
                "oss-cn-qingdao"       => '华北 1 oss-cn-qingdao',
                "oss-cn-beijing"       => '华北 2 oss-cn-beijing',
                "oss-cn-zhangjiakou"   => '华北 3 oss-cn-zhangjiakou',
                "oss-cn-huhehaote"     => '华北 5 oss-cn-huhehaote',
                "oss-cn-shenzhen"      => '华南 1 oss-cn-shenzhen',
                "oss-cn-hongkong"      => '香港 oss-cn-hongkong',
                "oss-us-west-1"        =>  '美国西部 1（硅谷）oss-us-west-1',
                "oss-us-east-1"        =>  '美国东部 1（弗吉尼亚）oss-us-east-1',
                "oss-ap-southeast-1"   =>  '亚太东南 1（新加坡）oss-ap-southeast-1',
                "oss-ap-southeast-2"   =>  '亚太东南 2（悉尼）oss-ap-southeast-2',
                "oss-ap-southeast-3"   =>  '亚太东南 3（吉隆坡） oss-ap-southeast-3',
                "oss-ap-southeast-5"   =>  '亚太东南 5 (雅加达) oss-ap-southeast-5',
                "oss-ap-northeast-1"   =>  '亚太东北 1（日本）oss-ap-northeast-1',
                "oss-ap-south-1"       =>  '亚太南部 1（孟买）oss-ap-south-1',
                "oss-eu-central-1"     =>  '欧洲中部 1（法兰克福）oss-eu-central-1',
                "oss-me-east-1"        =>  '中东东部 1（迪拜）oss-me-east-1',
                "other"                => '自定义'
            ),
            'oss-cn-qingdao',
            _t('区域选择，金融云需自定义'), '');
        $form->addInput($endPoint);

        $endPointType = new Typecho_Widget_Helper_Form_Element_Select('endPointType',
            array(
                ".aliyuncs.com"      => '外网',
                "-internal.aliyuncs.com"      => '内网',
            ),
            '.aliyuncs.com', '<label class="AliossForTypecho-mark-other-endpoint-hide">选择服务器与OSS连接方式</label>',
            '<span class="AliossForTypecho-mark-other-endpoint-hide">在你了解两种连接方式的不同作用的情况下修改此选项</span>');
        $form->addInput($endPointType);
        
        $otherEndPoint = new Typecho_Widget_Helper_Form_Element_Text('otherEndPoint', NULL, '自定义EndPoint，例如"oss-cn-qingdao.aliyuncs.com"',
            '<label class="AliossForTypecho-mark-other-endpoint-show">自定义EndPoint</label>', '<span class="AliossForTypecho-mark-other-endpoint-show">填写全部Endpoint，通常以\'.aliyuncs.com\'或\'-internal.aliyuncs.com\'结尾，开头不包含http://，结尾不包含"/"</span>');
        $form->addInput($otherEndPoint);
        
        $userDir = new Typecho_Widget_Helper_Form_Element_Text('userDir', NULL, 'typecho/',
            _t('要储存的路径'), _t('请填写文件储存的路径（相对OSS根目录），以字母或数字开头，以"/"结尾。留空则上传到根目录。'));
        $form->addInput($userDir);
        
        $cdnUrl = new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', NULL, '',
            _t('自定义（CDN）域名'), '请填写自定义域名，留空则使用外网Endpoint访问，以http://或https://开头，以"/"结尾');
        $form->addInput($cdnUrl);
        
        $ifLoaclSave = new Typecho_Widget_Helper_Form_Element_Radio('ifLoaclSave', array( "1" => '保留', "0" => '不保留' ), "1",
            _t('在服务器保留备份'), _t('是否在服务器保留备份'));
        $form->addInput($ifLoaclSave);
        
        echo '<script>
            window.onload = function() {
                (function () {
                    document.getElementsByName("des")[0].type = "hidden";
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
        </script>';
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
        
    /**
     * 上传文件处理函数
     *
     * @access public
     * @param array $file 上传的文件
     * @return mixed
     */
    public static function uploadHandle($file)
    {
        if (empty($file['name'])) {
            return FALSE;
        }

        $options = Typecho_Widget::widget('Widget_Options');
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = 'http://' . (($options->plugin('AliOssForTypecho')->endPoint === "other") ?
                $options->plugin('AliOssForTypecho')->otherEndPoint :
                $options->plugin('AliOssForTypecho')->endPoint . $options->plugin('AliOssForTypecho')->endPointType);
        $access_id   = $options->plugin('AliOssForTypecho')->accessKeyId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKeySecret;
        
        $ext = self::getExtentionName($file['name']);

        if (!self::checkFileType($ext)) {
            return FALSE;
        }

        date_default_timezone_set('PRC');

        $file_origin_name = self::getSafeName($file['name']);
        $file_id = substr(time(), 5) . sprintf('%u', crc32(uniqid()));

        $relative_path = date('Y/m/d/') . $file_id . '/' . $file_origin_name;
        $object_name = $userDir . $relative_path;

        if (isset($file['tmp_name'])) {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $content = $file['bytes'];
        } else {
            return FALSE;
        }
        try {
            $client = new OssClient($access_id, $access_key, $end_point);
        } catch (Exception $e) {
            throw new Exception( $e->getMessage());
        }

        $ali_response = $client->putObject($bucket_name, $object_name, $content);

        if (200 != $ali_response['info']['http_code']) {
            return FALSE;
        } else {
            $object_url = $ali_response['info']["url"];

            $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
                         
            if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) {
              
                $localPath = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                              defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__) 
                              . date('Y/m/d/') . $file_id . '/';
                $mkdirSuccess = TRUE;
                if (!is_dir($localPath)) {
                    if (!self::makeUploadDir($localPath)) {
                        $mkdirSuccess = FALSE;
                    }
                }
                $error_log_path = self::log_path;
                if ($mkdirSuccess) {
                    if (file_put_contents($localPath.$file_origin_name, $content)) {
                    } else {
                        $error = '错误：保存文件失败' . "\r\n" .
                                 '远程文件：' . $object_url . "\r\n" .
                                 '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        error_log($error, 3, $error_log_path . "error.log");
                    }
                } else {
                    $error = '错误：创建目录失败' . "\r\n" .
                             '创建路径：' . $localPath . "\r\n" .
                             '远程文件：' . $object_url . "\r\n" .
                             '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    error_log($error, 3, $error_log_path . "error.log");
                }
            }

            return array(
                'name' => $file_origin_name,
                'path' => $relative_path,
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

        $options = Typecho_Widget::widget('Widget_Options');
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = 'http://' . (($options->plugin('AliOssForTypecho')->endPoint === "other") ?
                $options->plugin('AliOssForTypecho')->otherEndPoint :
                $options->plugin('AliOssForTypecho')->endPoint . $options->plugin('AliOssForTypecho')->endPointType);
        $access_id   = $options->plugin('AliOssForTypecho')->accessKeyId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKeySecret;

        $ext = self::getExtentionName($file['name']);
        
        if ($content['attachment']->type != $ext) {
            return false;
        }
         
        $path = $content['attachment']->path;
        
        $object_name = $userDir . $path;

        if (isset($file['tmp_name'])) {
            $newContent = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $newContent = $file['bytes'];
        } else {
            return false;
        }

        try {
            $client = new OssClient($access_id, $access_key, $end_point);
        } catch (Exception $e) {
            throw new Exception( $e->getMessage());
        }

        $ali_response = $client->putObject($bucket_name, $object_name, $newContent);

        if (200 != $ali_response['info']['http_code']) {
            return FALSE;
        } else {
            $object_url = $ali_response["info"]["url"];

            $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
                         
            if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine())
            {
                $localFile = Typecho_Common::url(self::UPLOAD_DIR . $path, 
                    defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);   
                $localPath = dirname($localFile);

                $mkdirSuccess = TRUE;
                if (!is_dir($localPath)) {
                    if (!self::makeUploadDir($localPath)) {
                        $mkdirSuccess = FALSE;
                    }
                }

                $error_log_path = self::log_path;
                if ($mkdirSuccess)
                {
                    $deleteLacalFileSuccess = unlink($localFile);
                    if (!$deleteLacalFileSuccess)
                    {
                        $error_log_path = self::log_path;
                        if (!is_dir($error_log_path))
                        {
                            self::makeUploadDir($error_log_path);          
                        }
                        $error = '错误：删除文件失败导致无法修改文件' . "\r\n" .
                                 '文件：' . $localFile . "\r\n" .
                                 '远程文件：' . $object_url . "\r\n" .
                                 '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        error_log($error, 3, $error_log_path . "error.log");
                    } else {
                        if (file_put_contents($localFile, $newContent)) {
                        } else 
                        {
                            $error = '错误：保存文件失败' . "\r\n" .
                                     '远程文件：' . $object_url . "\r\n" .
                                     '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                            error_log($error, 3, $error_log_path . "error.log");
                        }
                    }
                } else 
                {
                    $error = '错误：创建目录失败' . "\r\n" .
                             '创建路径：' . $localPath . "\r\n" .
                             '远程文件：' . $object_url . "\r\n" .
                             '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                    error_log($error, 3, $error_log_path . "error.log");
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

        try {
            $client = new OssClient($access_id, $access_key, $end_point);
        } catch (Exception $e) {
            throw new Exception( $e->getMessage());
        }

        $path = $content['attachment']->path;
        $object_name = $userDir . $path;
        $ali_response = $client->deleteObject($bucket_name, $object_name);
        
        $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
        if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) {
            $localPath = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                                  defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__) 
                                  . $path;
                                  
            $deleteLacalFileSuccess = unlink($localPath);

            if (!$deleteLacalFileSuccess) 
            {
                $error_log_path = self::log_path;
                if (!is_dir($error_log_path))
                {
                    self::makeUploadDir($error_log_path);          
                }
                $error = '错误：删除文件失败' . "\r\n" .
                         '文件：' . $localPath . "\r\n" .
                         '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                error_log($error, 3, $error_log_path . "error.log");
            }
        }
        return ($ali_response['info']['http_code'] === 204);
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content) {
        $options = Typecho_Widget::widget('Widget_Options');
        
        $cdnUrl  = $options->plugin('AliOssForTypecho')->cdnUrl;
        $userDir = $options->plugin('AliOssForTypecho')->userDir;
        if ($cdnUrl == '') {
            $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
            $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                            $options->plugin('AliOssForTypecho')->otherEndPoint : 
                            $options->plugin('AliOssForTypecho')->endPoint;
            return 'https://' . $bucket_name . '.' . $end_point . '.aliyuncs.com/' . $userDir . $content['attachment']->path;
        } else {
            return $cdnUrl . $userDir . $content['attachment']->path;
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

        while (!is_dir($current) && false !== strpos($path, '/')) 
        {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) 
        {
            return true;
        }

        if (!@mkdir($last)) 
        {
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
    private static function getSafeName(&$name)
    {
        $name = str_replace(array('"', '<', '>'), '', $name);
        $name = str_replace('\\', '/', $name);
        $name = false === strpos($name, '/') ? ('a' . $name) : str_replace('/', '/a', $name);
        $info = pathinfo($name);
        $name = substr($info['basename'], 1);
        return $name;
    }

    private static function getExtentionName(&$name)
    {
        $info = pathinfo($name);
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }
}
