<?php

require_once 'AliOssSdk/sdk.class.php';

/**
 * AliyunOSS储存Typecho上传附件.
 * 
 * @package AliOssForTypecho 
 * @author droomo.
 * @version 1.0.1
 * @link http://www.droomo.top/ 
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
    {   $des = new Typecho_Widget_Helper_Form_Element_Text('des', NULL, '', _t('插件使用说明；'), 
                _t('<ol>
                      <li>插件基于阿里云oss_php_sdk_20150819开发包开发，若以后SDK开发包更新导致插件不可用，请到 <a target="_blank" href="http://www.droomo.top/">我的博客</a> ^ - ^获取新版本插件，如果我还用typecho还用阿里云就会更新。<br><br></li>
                      <li>阿里云OSS支持自定义域名到OSS获取文件，如果你的站点运行在阿里云ECS或ACE并十分清楚阿里云服务的内网互通规则，配置连接Bucket与获取文件使用不同的链接可以节省流量。如果你不清楚这些，那么就选择Bucket所在节点的外网地址。<br><br></li>
                      <li>运行在云应用引擎上的站点可以使用本插件，插件不会保存文件到服务器，“在服务器保留备份”选项无效。<br><br></li>
                      <li>若开启“在服务器保留备份”功能：<br>插件尽在上传到OSS失败或者遇到其他错误时会返回错误信息，成功保存到OSS但是没有成功保存到服务器的情况下，插件不会抛出异常，上传过程会继续进行，但是会在' . __TYPECHO_ROOT_DIR__ . self::log_path . '目录下生成错误日志"error.log"，请定期查阅并清理。<br><br></li>
                      <li>未作容错性检测，配置错误会导致上传失败。<br><br></li>
                      <li>Typecho原因无法上传大写扩展名文件，本插件不做修补，等待Typecho更新。<br><br></li>
                      <li>如有问题请到 <a target="_blank" href="http://www.droomo.top/">我的博客</a> 留言</li>
                    </ol>'));
        $form->addInput($des);
        
        $buketName = new Typecho_Widget_Helper_Form_Element_Text('bucketName', NULL, 'yourself bucketName',
            _t('Bucket名称'), _t('请填写Buket名称'));
        $form->addInput($buketName->addRule('required', _t('必须填写Bucket名称')));

        $accessId = new Typecho_Widget_Helper_Form_Element_Text('accessId', NULL, 'yourself AccessId',
            _t('ACCESS_ID'), _t('请填写ACCESS_ID'));
        $form->addInput($accessId->addRule('required', _t('必须填写ACCESS_ID')));

        $accessKey = new Typecho_Widget_Helper_Form_Element_Text('accessKey', NULL, 'yourself AccessKey',
            _t('ACCESS_KEY'), _t('请填写请填写ACCESS_KEY'));
        $form->addInput($accessKey->addRule('required', _t('必须填写ACCESS_KEY')));

        $endPoint = new Typecho_Widget_Helper_Form_Element_Select('endPoint', 
            array(
                  "oss-cn-qingdao.aliyuncs.com"               => '青岛节点外网地址：           oss-cn-qingdao.aliyuncs.com',
                  "oss-cn-qingdao-internal.aliyuncs.com"      => '青岛节点内网地址：           oss-cn-qingdao-internal.aliyuncs.com',
                  "oss-cn-beijing.aliyuncs.com"               => '北京节点外网地址：           oss-cn-beijing.aliyuncs.com',
                  "oss-cn-beijing-internal.aliyuncs.com"      => '北京节点内网地址：           oss-cn-beijing-internal.aliyuncs.com',
                  "oss-cn-hangzhou.aliyuncs.com"              => '杭州节点外网地址：           oss-cn-hangzhou.aliyuncs.com',
                  "oss-cn-hangzhou-internal.aliyuncs.com"     => '杭州节点内网地址：           oss-cn-hangzhou-internal.aliyuncs.com',
                  "oss-cn-hongkong.aliyuncs.com"              => '香港节点外网地址：           oss-cn-hongkong.aliyuncs.com',        
                  "oss-cn-hongkong-internal.aliyuncs.com"     => '香港节点内网地址：           oss-cn-hongkong-internal.aliyuncs.com',
                  "oss-cn-shenzhen.aliyuncs.com"              => '深圳节点外网地址：           oss-cn-shenzhen.aliyuncs.com',
                  "oss-cn-shenzhen-internal.aliyuncs.com"     => '深圳节点内网地址：           oss-cn-shenzhen-internal.aliyuncs.com',
                  "oss-cn-shanghai.aliyuncs.com"              => '上海节点外网地址：           oss-cn-shanghai.aliyuncs.com',
                  "oss-cn-shanghai-internal.aliyuncs.com"     => '上海节点内网地址：           oss-cn-shanghai-internal.aliyuncs.com',
                  "oss-us-west-1.aliyuncs.com"                => '美国硅谷节点外网地址：       oss-us-west-1.aliyuncs.com', 
                  "oss-us-west-1-internal.aliyuncs.com"       => '美国硅谷节点内网地址：       oss-us-west-1-internal.aliyuncs.com', 
                  "oss-ap-southeast-1.aliyuncs.com"           => '亚太（新加坡）节点外网地址： oss-ap-southeast-1.aliyuncs.com', 
                  "oss-ap-southeast-1-internal.aliyuncs.com"  => '亚太（新加坡）节点内网地址： oss-ap-southeast-1-internal.aliyuncs.com',
                  "other"                                     => '其他'
                  ),
            'oss-cn-qingdao.aliyuncs.com', _t('连接Bucket结点所用地址'), _t('参见使用说明第二条'));
        $form->addInput($endPoint);
        
        $otherEndPoint = new Typecho_Widget_Helper_Form_Element_Text('otherEndPoint', NULL, '填写其他节点',
            '', _t('不包含http://，结尾不包含"/"'));
        $form->addInput($otherEndPoint);
        
        $userDir = new Typecho_Widget_Helper_Form_Element_Text('userDir', NULL, 'typecho/',
            _t('要储存的路径'), _t('请填写文件储存的路径（相对OSS根目录），以字母或数字开头，以"/"结尾。留空则上传到根目录。'));
        $form->addInput($userDir);
        
        $cdnUrl = new Typecho_Widget_Helper_Form_Element_Text('cdnUrl', NULL, '',
            _t('自定义域名'), _t('请填写自定义域名，留空则访问OSS源，不包含http://，结尾不包含"/"'));
        $form->addInput($cdnUrl);
        
        $ifLoaclSave = new Typecho_Widget_Helper_Form_Element_Radio('ifLoaclSave', array( "1" => '保留', "0" => '不保留' ), "1",
            _t('在服务器保留备份'), _t('是否在服务器保留备份'));
        $form->addInput($ifLoaclSave);
        
        echo '<script>
          window.onload = function() 
          {
            var select = document.getElementsByName("endPoint")[0];
            var input = document.getElementsByName("otherEndPoint")[0];
            document.getElementsByName("des")[0].type = "hidden";
            if (!(select.value === "other"))
            {
              input.type = "hidden";
            }
            document.getElementsByName("endPoint")[0].onchange = function() 
            {
              if (select.value === "other") 
              {
                input.type = "text";
              } else 
              {
                input.type = "hidden";
              }
            }
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
        if (empty($file['name'])) 
        {
            return FALSE;
        }
        
        $ext = self::getSafeName($file['name']);

        if (!self::checkFileType($ext)) 
        {
            return FALSE;
        }

        date_default_timezone_set('PRC');
        $testDate = date('Y/m/d/');
        $options = Typecho_Widget::widget('Widget_Options');
        $path = $testDate;
        //error_log(date('h:m:sa'), 3, self::log_path . 'error.log');
                
        //获取文件名
        $fileName = substr(time(), 5) . sprintf('%u', crc32(uniqid())) . '.' . $ext;
        
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                        $options->plugin('AliOssForTypecho')->otherEndPoint : 
                        $options->plugin('AliOssForTypecho')->endPoint;
        $access_id   = $options->plugin('AliOssForTypecho')->accessId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKey;
        
        $localFile = $path . $fileName;
        $object_name = $userDir . $localFile;
               
        if (isset($file['tmp_name']))
        {
            $content = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) 
        {
            $content = $file['bytes'];
        } else 
        {
            return FALSE;
        }
        
        $fileSize = strlen($content);
        $ali_options = array(
            'content' => $content,
            'length' => $fileSize,
            ALIOSS::OSS_HEADERS => array(
                'Content-Encoding' => 'utf-8',
                'Content-Language' => 'zh-CN',
            ),
        );
        
        $client = new ALIOSS($access_id, $access_key, $end_point);
        $client->set_enable_domain_style(TRUE);

        $ali_response = $client->upload_file_by_content($bucket_name, $object_name, $ali_options);
                        
        if (200 != $ali_response->status) 
        {
            return FALSE;
        } else 
        {
            $object_url = $ali_response->header["_info"]["url"];

            $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
                         
            if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) 
            {
              
                $localPath = Typecho_Common::url(defined('__TYPECHO_UPLOAD_DIR__') ? __TYPECHO_UPLOAD_DIR__ : self::UPLOAD_DIR,
                              defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__) 
                              . '/' . $path;
                $mkdirSuccess = TRUE;
                //创建上传目录
                if (!is_dir($localPath)) 
                {
                    if (!self::makeUploadDir($localPath))
                    {
                        $mkdirSuccess = FALSE;
                    }
                }

                $saveOnServerSuccess = FALSE;
                
                //保存文件到服务器
                $error_log_path = self::log_path;
                if ($mkdirSuccess) 
                {
                    if (file_put_contents($localPath.$fileName, $content)) 
                    {
                        $saveOnServerSuccess = TRUE;
                    } else 
                    {
                        $error = '错误：保存文件失败' . "\r\n" .
                                 '远程文件：' . $object_url . "\r\n" .
                                 '时间：' . date('Y-m-d h:i:sa') . "\r\n\r\n";
                        error_log($error, 3, $error_log_path . "error.log");
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
        
        $name = $file['name'];
        //返回相对存储路径
        return array(
            'name' => $name,
            'path' => $localFile,
            'size' => $fileSize,
            'type' => $ext,
            'mime' => Typecho_Common::mimeContentType($localPath . $fileName)
        );
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

        $ext = self::getSafeName($file['name']);
        
        if ($content['attachment']->type != $ext) {
            return false;
        }

        if (isset($file['tmp_name'])) {
            $newContent = file_get_contents($file['tmp_name']);
        } else if (isset($file['bytes'])) {
            $newContent = $file['bytes'];
        } else {
            return false;
        }

        $options = Typecho_Widget::widget('Widget_Options');
        $date = new Typecho_Date($options->gmtTime);        
         
        $path = $content['attachment']->path;
        
        $userDir     = $options->plugin('AliOssForTypecho')->userDir;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                        $options->plugin('AliOssForTypecho')->otherEndPoint : 
                        $options->plugin('AliOssForTypecho')->endPoint;
        $access_id   = $options->plugin('AliOssForTypecho')->accessId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKey;
        
        $object_name = $userDir . $path;
        
        $fileSize = strlen($newContent);
        $ali_options = array(
            'content' => $newContent,
            'length' => $fileSize,
            ALIOSS::OSS_HEADERS => array(
                'Content-Encoding' => 'utf-8',
                'Content-Language' => 'zh-CN',
            ),
        );
        
        $client = new ALIOSS($access_id, $access_key, $end_point);
        $client->set_enable_domain_style(TRUE);

        $ali_response = $client->upload_file_by_content($bucket_name, $object_name, $ali_options);

        if (200 != $ali_response->status) 
        {
            return FALSE;
        } else 
        {
            $object_url = $ali_response->header["_info"]["url"];

            $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
                         
            if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) 
            {
                $localFile = Typecho_Common::url(self::UPLOAD_DIR . $path, 
                    defined('__TYPECHO_UPLOAD_ROOT_DIR__') ? __TYPECHO_UPLOAD_ROOT_DIR__ : __TYPECHO_ROOT_DIR__);   
                $localPath = dirname($localFile);

                $mkdirSuccess = TRUE;
                //创建上传目录
                if (!is_dir($localPath)) 
                {
                    if (!self::makeUploadDir($localPath))
                    {
                        $mkdirSuccess = FALSE;
                    }
                }

                $saveOnServerSuccess = FALSE;
                
                //保存文件到服务器
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
                        if (file_put_contents($localFile, $newContent)) 
                        {
                            $saveOnServerSuccess = TRUE;
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
        
        //返回相对存储路径
        return array(
            'name' => $content['attachment']->name,
            'path' => $path,
            'size' => $fileSize,
            'type' => $content['attachment']->type,
            'mime' => $content['attachment']->mime
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
        $access_id   = $options->plugin('AliOssForTypecho')->accessId;
        $access_key  = $options->plugin('AliOssForTypecho')->accessKey;
        $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
        $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                        $options->plugin('AliOssForTypecho')->otherEndPoint : 
                        $options->plugin('AliOssForTypecho')->endPoint;
        $ali_options = null;
        
        $client = new ALIOSS($access_id, $access_key, $end_point);

        $path = $content['attachment']->path;
        $object_name = $userDir . $path;
        $ali_response = $client->delete_object($bucket_name, $object_name, $ali_options);
        
        $ifLoaclSave = $options->plugin('AliOssForTypecho')->ifLoaclSave;
        if ($ifLoaclSave === "1" && !Typecho_Common::isAppEngine()) 
        {
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
        return ($ali_response->status == 200);
    }

    /**
     * 获取实际文件绝对访问路径
     *
     * @access public
     * @param array $content 文件相关信息
     * @return string
     */
    public static function attachmentHandle(array $content)
    {
        $options = Typecho_Widget::widget('Widget_Options');
        
        $cdnUrl  = $options->plugin('AliOssForTypecho')->cdnUrl;
        $userDir = $options->plugin('AliOssForTypecho')->userDir;
        if ($cdnUrl == '')
        {
            $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
            $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                            $options->plugin('AliOssForTypecho')->otherEndPoint : 
                            $options->plugin('AliOssForTypecho')->endPoint;
            return 'http://' . $bucket_name . '.' . $end_point . '/' . $userDir . $content['attachment']->path;
        } else
        {
            return 'http://' . $cdnUrl . '/' . $userDir . $content['attachment']->path;
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
        $options = Typecho_Widget::widget('Widget_Options');
        
        $cdnUrl      = $options->plugin('AliOssForTypecho')->cdnUrl;
        $userDir      = $options->plugin('AliOssForTypecho')->userDir;
        if ($cdnUrl == '')
        {
            $bucket_name = $options->plugin('AliOssForTypecho')->bucketName;
            $end_point   = ($options->plugin('AliOssForTypecho')->endPoint === "other") ? 
                            $options->plugin('AliOssForTypecho')->otherEndPoint : 
                            $options->plugin('AliOssForTypecho')->endPoint;
            $filePath =  'http://' . $bucket_name . '.' . $end_point . $userDir . $content['attachment']->path;
        } else
        {
            $filePath = 'http://' . $cdnUrl  . $userDir . $content['attachment']->path;
        }
        
        return file_get_contents($filePath);
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
    
        //return isset($info['extension']) ? $info['extension'] : '';
        return isset($info['extension']) ? strtolower($info['extension']) : '';
    }
}
