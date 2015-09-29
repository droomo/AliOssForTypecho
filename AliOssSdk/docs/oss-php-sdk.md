# OSS PHP SDK 开发文档

## 简介
* 简介
 * 阿里云存储服务（Open Storage Service，简称OSS），是阿里云对外提供的海量，安全，低成本，高可靠的云存储服务。用户可以通过本文档提供的简单的REST接口，在任何时间、任何地点、任何互联网设备上进行上传和下载数据。基于OSS，用户可以搭建出各种多媒体分享网站、网盘、个人和企业数据备份等基于大规模数据的服务。

## ChangeHistory
* 2015.8.20
 * 修复了在有response-content-disposition等HTTP Header的时候，下载签名不对的问题。

* 2015.7.1
 * 新增了转换响应body的设置，目前支持xml,array,json三种格式。默认为xml
 * 新增copy_upload_part方法
 * 支持sts
 * 调整签名url中$options参数的位置
 * fix read_dir循环遍历的问题

*2015.3.30
 * 增加了referer和lifecycle相关的接口。增加了upload by file和multipart upload时候的content-md5检查的选项。
 * 增加了init_multipart_upload 直接获取string类型的upload
 * 调整了batch_upload_file函数的返回值，从原来的空，变成boolean，成功为true，失败为false。
 * 调整了sdk.class.php 中工具函数的位置，放置在util/oss_util.class.php中，如果需要引用，需要增加OSSUtil::， 并引用该文件。
 
 * 修复的Bug：
 * 修复Copy object的过程中无法修改header的问题。
 * 修复upload part时候自定义upload的语法错误。
 * 修复上传的时候，office2007文件的mimetype无法设置正确的问题。
 * 修复batch_upload_file时候，遇到空目录会超时退出的问题。
  
## 名词解释
 * Bucket
 * Object
 * Multipart
 * LifeCycle
 * WebSite
 * Logging
 * CORS

## 命名规范
 * Bucket命名规范
  * 只能包括小写字母，数字，短横线（-）
  * 必须以小写字母或者数字开头
  * 长度必须在3-63字节之间
 * Object命名规范
  * 使用UTF-8编码
  * 长度必须在1-1023字节之间
  * 不能以“/”或者“\”字符开头
  * 不能含有“\r”或者“\n”的换行符
## 环境准备
* windows系统
* linux系统
* mac系统

## 前置依赖检查
* PHP扩展库检测
 * 在使用前请优先检查curl,mbstring,SimpleXML,json,iconv等扩展库是否开启，如果未开启，请修改php.ini开启相应的扩展库

 
## 初始化资源
* 普通方式初始化ALIOSS

  *  在通常情况下,初始化只需要提供用户的accessId,accessKey,endPoint等信息即可完成初始化。同时完成必须信息的配置
  
   	`
	
		$access_id = "请填入accessId";
		$access_key = "请填入accessKey";
		$end_point = "操作集群的endpoint";
		$client = ALIOSS($access_id,$access_key,$end_point);
		
		//是否以域名的格式显示，如果为true表示以域名的格式显示数据，如http://bucket.domain/object,
		//如果为false，则为http://domain/bucket/object格式
		$client->set_enable_domain_style(true);

  	`
  
* 使用sts方式初始化ALIOSS
 * 如果是移动端的开发者，需要使用sts服务，只需要在初始化的时候调用sts的api生成临时的accessId,accessKey，以及securityToken即可。
    
	`
		$access_id = "调用sts接口得到的临时access_id";
		$access_key = "调用sts接口得到的临时access_key";
		$end_point = "操作集群的endpoint";
		$security_token = "调用sts接口得到的临时security_token";
		$client = ALIOSS($access_id,$access_key,$end_point,$security_token);

	`

## Bucket相关操作
* 获取bucket列表
 * 示例代码
	
		`
			$options = null;
			$response = $client->list_bucket($options); 

		`
 * 参数说明
 	
		`
			$options 可选参数,无需设置

		`
 * 响应结果
		、
        将结果Response转换成array得到，下同
           Array(
               [status] => 200
               [header] => Array(
                   [date] => Wed, 01 Jul 2015 09:21:15 GMT
                   [content-type] => application/xml
                   [content-length] => 6266
                   [connection] => close
                   [server] => AliyunOSS
                   [x-oss-request-id] => 5593B10B58DB3AB752154A62
               )
               [body] => Array(
                   [ListAllMyBucketsResult] => Array(
                       [Owner] => Array (
                            [ID] => 128257
                            [DisplayName] => 128257
                       )
                       [Buckets] => Array(
                            [Bucket] => Array(
                               [0] => Array(
                                   [Location] => oss-cn-hangzhou
                                    [Name] => 33331111
                                    [CreationDate] => 2014-08-27T03:04:20.000Z
                                 )
                                 [1] => Array (
                                   [Location] => oss-cn-qingdao
                                    [Name] => a-00000000000000000001
                                    [CreationDate] => 2015-05-22T05:30:40.000Z
                                 )

                             )

                           )

                   )

               )

           )
		、
* 创建bucket
 * 示例代码
	
		`
			$bucket_name = "bucket name";
			$acl = ALIOSS::OSS_ACL_TYPE_PRIVATE;
			$options = null;
			$response = $client->create_bucket($bucket_name,$acl,$options);

		`
 * 参数说明
 
		`

			$bucket_name 必选参数，需要符合bucket命名规范
			$acl 必选参数，只能从private,public-read,public-read-write中任选一个，分别和以下常量映射
				ALIOSS::OSS_ACL_TYPE_PRIVATE，
				ALIOSS::OSS_ACL_TYPE_PUBLIC_READ，
				ALIOSS::OSS_ACL_TYPE_PUBLIC_READ_WRITE
			$options 可选参数，无需设置

		`

 * 响应结果
 
		`
        将结果Response转换成array得到，下同
			Array(
    			[status] => 200
    			[header] => Array(
            		[date] => Wed, 01 Jul 2015 09:55:18 GMT
            		[content-length] => 0
            		[connection] => close
            		[server] => AliyunOSS
            		[x-oss-request-id] => 5593B906031C87E546154CC1
        		)

    			[body] => 
			)
			
		`

* 删除bucket
 * 示例代码
 
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->delete_bucket($bucket_name,$options);

		`

 * 参数说明
 		
		`

			$bucket_name 必选参数，需要符合bucket命名规范
			$options 可选参数,无需设置

		`
 * 响应结果
 
		`
		
        将结果Response转换成array得到，下同
			Array(
    			[status] => 204
    			[header] => Array(
            		[date] => Wed, 01 Jul 2015 10:08:45 GMT
            		[content-length] => 0
            		[connection] => close
            		[server] => AliyunOSS
            		[x-oss-request-id] => 5593BC2D58DB3AB752155156
        		)

    			[body] => 
			)

		`
* 获取bucket Acl
 * 示例代码
 		
		`
			
			$bucket_name = "bucket name";
			$options = null;
			$response = $client->get_bucket_acl($bucket_name,$options);
				

		`
 * 参数说明
 		
		`
			$bucket_name 必选参数，需要符合bucket命名规范
			$options 可选参数,无需设置
			
		`
 * 响应结果
 
		`
        将结果Response转换成array得到，下同
			
			Array(
    			[status] => 200
    			[header] => Array(
	        		[date] => Wed, 01 Jul 2015 10:17:41 GMT
			        [content-type] => application/xml
			        [content-length] => 239
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5593BE45031C87E54615500F
			    )

			    [body] => Array(
			        [AccessControlPolicy] => Array(
			            [Owner] => Array(
			                [ID] => 128257
			                [DisplayName] => 128257
			            )
			
						[AccessControlList] => Array(
							[Grant] => public-read
						)
					)
			    )
			)
		
		`

* 设置bucket Acl
 * 示例代码
 		
		`
			$bucket_name = "bucket name";
			$options = null;
			$response = $client->set_bucket_acl($bucket_name,$options);			
		`

 * 参数说明
 
		`
			$bucket_name 必选参数，需要符合bucket命名规范
			$options 可选参数,无需设置
		`

 * 响应结果
 
		`
        将结果Response转换成array得到，下同
			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Wed, 01 Jul 2015 11:08:31 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5593CA2F031C87E5461557B5
			    )
			
			    [body] => 
			)
		`

## Object相关操作
* 获取Object列表
 * 示例代码

		`
			$bucket_name = 'bucket name';
			$options = array(
				'delimiter' => '/',
				'prefix' => '',
				'max-keys' => 5,
				'marker' => '',
			);
			
			$response = $client->list_object($$bucket_name,$options);

		`
		

 * 参数说明

	
		`
			
			$bucket_name 必选参数，需要符合bucket命名规范
			$options 可选参数，其中的参数说明如下
				delimiter 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter
                字符之间的object作为一组元素——CommonPrefixes。
				prefix 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中
                仍会包含prefix
				max-keys 限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000
				marker 设定结果从marker之后按字母排序的第一个开始返回
				
		` 


 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 07:59:32 GMT
			        [content-type] => application/xml
			        [content-length] => 1466
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594EF64031C87E546160F24
			    )
			
			    [body] => Array(
			        [ListBucketResult] => Array(
			            [Name] => common-bucket
			            [Prefix] => 
			            [Marker] => 
			            [MaxKeys] => 5
			            [Delimiter] => /
			            [IsTruncated] => true
			            [NextMarker] => metro_driver.dll
			            [Contents] => Array(
			                [0] => Array(
			                    [Key] => chrome_elf.dll
			                    [LastModified] => 2015-07-01T03:44:58.000Z
			                    [ETag] => "78CE940FD1CCDF6F743EE1A9AED8AAD8"
			                    [Type] => Normal
			                    [Size] => 133960
			                    [StorageClass] => Standard
			                    [Owner] => Array(
			                        [ID] => 128257
			                        [DisplayName] => 128257
			                    )
			                )
			
			                [1] => Array(
			                    [Key] => delegate_execute.exe
			                    [LastModified] => 2015-06-29T09:18:41.000Z
			                    [ETag] => "37C49C4E0EC4E0D96B6EBBA2190E8824"
			                    [Type] => Normal
			                    [Size] => 692040
			                    [StorageClass] => Standard
			                    [Owner] => Array(
			                        [ID] => 128257
			                        [DisplayName] => 128257
			                    )
			                )
						}
			
			            [CommonPrefixes] => Array(
			                [0] => Array(
			                    [Prefix] => common-folder/
			                )
			
			                [1] => Array(
			                    [Prefix] => common-folder2/
			                )
						)
			        )
			    )
			)

		`


* 创建虚拟文件夹
 * 示例代码
 
		`

			$bucket_name = 'bucket name';
			$dir_name = 'directory name';
			$options = null;
			$response  = $client->create_object_dir($bucket_name,$dir_name,$options);

		`


 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$dir_name 必选参数，必须符合object命名规范
			$options 可选参数，对于虚拟文件夹而言，无需设置一些header头			
		

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 08:10:16 GMT
			        [content-length] => 0
			        [connection] => close
			        [etag] => "D41D8CD98F00B204E9800998ECF8427E"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594F1E8031C87E5461610B9
			    )
			
			    [body] => 
			)

		`


* 上传文件(直接指定内容)
 * 示例代码
 
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$content  = 'object content';
			$options = array(
				'content' => $content,
				'length' => strlen($content),
				ALIOSS::OSS_HEADERS => array(
					'Expires' => 'Fri, 28 Feb 2012 05:38:42 GMT',
		            'Cache-Control' => 'no-cache',
		            'Content-Disposition' => 'attachment;filename=oss_download.log',
		            'Content-Encoding' => 'utf-8',
					'Content-Language' => 'zh-CN',
		            'x-oss-server-side-encryption' => 'AES256',
				),
			);	
			$response = $obj->upload_file_by_content($bucket_name,$object_name,$options);

		`

 * 参数说明

		
		`
		
			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$options 必选参数，该参内指定上传所需要的各种信息，具体各参数说明如下
				content 上传object的内容
				length  上传object的大小
				ALIOSS::OSS_HEADERS 该参数可选，如果指定，则可以设置该object的一些meta信息，可以设置的头信息如下：
					Expires 过期时间（milliseconds）
					Cache-Control 指定该Object被下载时的网页的缓存行为
					Content-Disposition 指定该Object被下载时的名称
					Content-Encoding 指定该Object被下载时的内容编码格式
					Content-Language 指定object被下载时候的语言
					x-oss-server-side-encryption 指定oss创建object时的服务器端加密编码算法

		` 


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
					[date] => Thu, 02 Jul 2015 08:24:11 GMT
			        [content-length] => 0
			        [connection] => close
			        [etag] => "9BA9EF6DDFBE14916FA2D3337B427774"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594F52B031C87E5461612D1
			    )
			
			    [body] => 
			)

		`

* 上传文件(指定上传路径)
 * 示例代码
 
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$file_path = "upload file path";
		    $options = array(
		        ALIOSS::OSS_HEADERS => array(
		            'Expires' => 'Fri, 28 Feb 2012 05:38:42 GMT',
		            'Cache-Control' => 'no-cache',
		            'Content-Disposition' => 'attachment;filename=oss_download.gz',
		            'Content-Encoding' => 'utf-8',
		            'Content-Language' => 'zh-CN',
		            'x-oss-server-side-encryption' => 'AES256',
		
		        ),
		    );
			$response = $obj->upload_file_by_file($bucket,$object,$file_path,$upload_file_options);

		`

 * 参数说明

		`
		
			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$file_path 必选参数，文件所在的路径
			$options 必选参数，该参内指定上传所需要的各种信息，具体各参数说明如下
				content 上传object的内容
				length  上传object的大小
				ALIOSS::OSS_HEADERS 该参数可选，如果指定，则可以设置该object的一些meta信息，可以设置的头信息如下：
					Expires 过期时间（milliseconds）
					Cache-Control 指定该Object被下载时的网页的缓存行为
					Content-Disposition 指定该Object被下载时的名称
					Content-Encoding 指定该Object被下载时的内容编码格式
					Content-Language 指定object被下载时候的语言
					x-oss-server-side-encryption 指定oss创建object时的服务器端加密编码算法

		` 

 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 08:41:10 GMT
			        [content-length] => 0
			        [connection] => close
			        [etag] => "4B12FF064A3BBFE0AE5A1314E77FF0DF"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594F91358DB3AB7521617CA
			    )
			
			    [body] => 
			)

		`


* 拷贝Object
 * 示例代码
 
		`

			$from_bucket = 'copy from bucket';
			$from_object = 'copy from object';
			$to_bucket = 'copy to bucket';
			$to_object = 'copy to object';
	        $options = array(
	            ALIOSS::OSS_HEADERS => array(
	                'x-oss-copy-source-if-match' => 'E024E425254F1EEDB237F69F854CE883',
	                'x-oss-copy-source-if-none-match' => 'Thu, 18 Jun 2015 08:50:31 GMT',
	                'x-oss-copy-source-if-unmodified-since' => 'Thu, 18 Jun 2015 08:50:31 GMT',
	                'x-oss-copy-source-if-modified-since' => 'Thu, 18 Jun 2015 09:50:31 GMT',
	                'x-oss-metadata-directive' => 'COPY',
	                'x-oss-server-side-encryption' => 'AES256'
	            )
	        );
	
			$response = $obj->copy_object($from_bucket,$from_object,$to_bucket,$to_object,$options);

		`

 * 参数说明
 
		`

			$from_bucket 必选参数，源bucket，必须符合bucket命名规范
			$from_object 必选参数，源object，必须符合object命名规范
			$to_bucket 必选参数，目标bucket，必须符合bucket命名规范
			$to_object 必选参数，目标object，必须符合object命名规范
			$options 可选参数，如果需要设置，可以设置 ALIOSS::OSS_HEADERS 头参数，参数有如下的几个选项
				x-oss-copy-source-if-match 如果源Object的ETAG值和用户提供的ETAG相等，则执行拷贝操作；
				否则返回412 HTTP错误码（预处理失败）
				x-oss-copy-source-if-none-match 如果源Object自从用户指定的时间以后就没有被修改过，则执行拷贝操作；
				否则返回412 HTTP错误码（预处理失败）
				x-oss-copy-source-if-unmodified-since 如果传入参数中的时间等于或者晚于文件实际修改时间，则正常传输文件，
				并返回200 OK；否则返回412 precondition failed错误
				x-oss-copy-source-if-modified-since 如果源Object自从用户指定的时间以后被修改过，则执行拷贝操作；
				否则返回412 HTTP错误码（预处理失败）
				x-oss-metadata-directive 有效值为COPY和REPLACE。如果该值设为COPY，则新的Object的meta都从源Object复制过来；
				如果设为REPLACE，则忽视所有源Object的meta值，而采用用户这次请求中指定的meta值；其他值则返回400 HTTP错误码。
				注意该值为COPY时，源Object的x-oss-server-side-encryption的meta值不会进行拷贝；默认值为COPY
				x-oss-server-side-encryption 指定oss创建目标object时的服务器端熵编码加密算法，目前仅支持AES256

		`

 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-type] => application/xml
			        [content-length] => 184
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 09:26:28 GMT
			        [etag] => "E024E425254F1EEDB237F69F854CE883"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 559503C458DB3AB752161E83
			        [x-oss-server-side-encryption] => AES256
			    )
			
			    [body] => Array(
			        [CopyObjectResult] => Array(
			            [LastModified] => 2015-07-02T09:26:28.000Z
			            [ETag] => "E024E425254F1EEDB237F69F854CE883"
			        )
			    )
			)

		`


* 获取Object MetaData
 * 示例代码
 
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$options = null;
			$response = $client->get_object_meta($bucket_name,$object_name,$options);

		`

 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$options 可选参数，无需设置

		`

 * 响应结果

		`

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 09:03:40 GMT
			        [content-type] => plain/text
			        [content-length] => 10
			        [connection] => close
			        [accept-ranges] => bytes
			        [cache-control] => no-cache
			        [content-disposition] => attachment;filename=oss_download.log
			        [content-encoding] => utf-8
			        [content-language] => zh-CN
			        [etag] => "9BA9EF6DDFBE14916FA2D3337B427774"
			        [expires] => Fri, 28 Feb 2012 05:38:42 GMT
			        [last-modified] => Thu, 02 Jul 2015 08:38:10 GMT
			        [server] => AliyunOSS
			        [x-oss-object-type] => Normal
			        [x-oss-request-id] => 5594FE6C031C87E5461618B6
			        [x-oss-server-side-encryption] => AES256
			    )
			
			    [body] => 
			)

		`

* 删除单个Object
 * 示例代码
 
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$options = null;
			$response = $client->delete_object($bucket_name,$object_name,$options);

		`


 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$options 可选参数，无需设置

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 204
			    [header] => Array(
			        [content-length] => 0
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 10:01:00 GMT
			        [server] => AliyunOSS
			        [x-oss-request-id] => 55950BDC58DB3AB75216239D
			    )
			
			    [body] => 
			)


		`


* 删除多个Object
 * 示例代码
 
		
		`

			$bucket_name = 'bucket name';
			$objects = array(
				'delete object 1',
				'delete object 2',
				...
			);
			
			$options = array(
				'quiet' => false,
			);
			
			$response = $client->delete_objects($bucket_name,$objects,$options);

		`


 * 参数说明

		`
			$bucket_name 必选参数，必须符合bucket命名规范 
			$objects 必选参数，其中的object必须符合object命名规范
			$options 可选参数，此处可以根据实际情况选择删除的两种模式，quite 参数可有true|false两种选择，
				true OSS返回的消息体中只包含删除过程中出错的Object结果；如果所有删除都成功的话，则没有消息体。
				false OSS返回的消息体中会包含每一个删除Object的结果

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
		    [status] => 200
		    [header] => Array(
		        [content-type] => application/xml
		        [content-length] => 188
		        [connection] => close
		        [date] => Thu, 02 Jul 2015 10:06:00 GMT
		        [server] => AliyunOSS
		        [x-oss-request-id] => 55950D0858DB3AB752162459
		    )
		
		    [body] => Array(
		        [DeleteResult] => Array(
		            [Deleted] => Array(
		                [0] => Array(
		                    [Key] => delegate_execute.exe
		                )
		
		                [1] => Array(
		                    [Key] => metro_driver.dll
		                )
		            )
		        )
		    )


		`


* 下载Object
 * 示例代码
 
		`

			$bucket_name = 'download bucket';
			$object_name = 'download object';
			
			$options = array(
				ALIOSS::OSS_FILE_DOWNLOAD => "download path",
		        ALIOSS::OSS_RANGE  => '0-1',
			);	
			
			$response = $client>get_object($bucket_name,$object_name,$options);

		`

 * 参数说明
 
		
		`
			
			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$options 必选参数，该参数中必须设置ALIOSS::OSS_FILE_DOWNLOAD，ALIOSS::OSS_RANGE可选，可以根据实际情况设置；
			如果不设置，默认会下载全部内容
		

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同
	
			Array(
	    		[status] => 206
	    		[header] => Array(
	        	)
	
	    		[body] => 
			)

		`


## MultipartUpload相关操作
* 初始化 multipartUpload
 * 示例代码
 
		`

		    $bucket_name = 'bucket name';
		    $object_name = 'object name';
		    $options = array(
		        ALIOSS::OSS_HEADERS => array(
		            'Expires' => 'Fri, 28 Feb 2012 05:38:42 GMT',
		            'Cache-Control' => 'no-cache',
		            'Content-Disposition' => 'attachment;filename=oss_download.log',
		            'Content-Encoding' => 'utf-8',
		            'Content-Type' => 'plain/text',
		            'Content-Language' => 'zh-CN',
		            'x-oss-server-side-encryption' => 'AES256',
		        ),
		    );
		    $response = $client->initiate_multipart_upload($bucket_name,$object_name,$options);

		`

 * 参数说明
 
		`
		
			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$options 必选参数，该参内指定上传所需要的各种信息，具体各参数说明如下
				ALIOSS::OSS_HEADERS 该参数可选，如果指定，则可以设置该object的一些meta信息，可以设置的头信息如下：
					Expires 过期时间（milliseconds）
					Cache-Control 指定该Object被下载时的网页的缓存行为
					Content-Disposition 指定该Object被下载时的名称
					Content-Encoding 指定该Object被下载时的内容编码格式
					'Content-Type' => 'plain/text'指定Object响应时的MIME类型
					Content-Language 指定object被下载时候的语言
					x-oss-server-side-encryption 指定oss创建object时的服务器端加密编码算法

		` 

 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-type] => application/xml
			        [content-length] => 234
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 11:35:36 GMT
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5595220858DB3AB7521631B1
			        [x-oss-server-side-encryption] => AES256
			    )
			
			    [body] => Array(
			        [InitiateMultipartUploadResult] => Array(
			            [Bucket] => common-bucket
			            [Key] => multipart-upload-1435836936
			            [UploadId] => 154A34BD1FE24A90A025EB800AA392CC
			        )
			    )
			)

		`


* 上传Part
 * 示例代码
 
		`

		    $bucket_name = 'bucket name';
		    $object_name = 'object name';
		    $upload_id = 'upload id';
		    $options = array(
		        'fileUpload' => 'upload path',
		        'partNumber' => 1,
		        'seekTo' => 1,
		        'length' => 5242880,
		    );
		
		    $response = $client->upload_part($bucket_name,$object_name, $upload_id, $options);

		`


 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$upload_id 必选参数，上传part对应的multipart uploads Id
			$options 必选参数，该参内指定上传所需要的各种信息，具体各参数说明如下
				fileUpload 上传文件的路径
				partNumber 上传part的序号
				seekTo 读取上传文件的起始字节
				length 切片大小

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-length] => 0
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 11:35:36 GMT
			        [etag] => "3AE3AD480200A26738F10CBF2FFBE8B6"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5595220858DB3AB7521631B3
			        [x-oss-server-side-encryption] => AES256
			    )
			
			    [body] => 
			)

		`


* 拷贝Upload Part
 * 示例代码

		`

		    $from_bucket = 'copy from bucket';
		    $from_object = 'copy from object';
		    $to_bucket = 'copy to bucket';
		    $to_object = 'copy to object';
		    $part_number = 1;
		    $upload_id = 'copy to upload id';
		    $options = array(
		        'start' => 0,
		        'end' => 25000032,
		    );
		
		    $response = $client->copy_upload_part($from_bucket,$from_object,$to_bucket,$to_object,$part_number,$upload_id,$options);


		`

 * 参数说明

		`

			$from_bucket 必选参数，源bucket，必须符合bucket命名规范
			$from_object 必选参数，源object，必须符合object命名规范
			$to_bucket 必选参数，目标bucket，必须符合bucket命名规范
			$to_object 必选参数，目标object，必须符合object命名规范
			$part_number 必选参数，范围是1~10,000
			$upload_id 必选参数，初始化multipartupload返回的uploadid
			$options 可选参数，如果需要设置，可以设置isFullCopy,startRange,endRange和 
				ALIOSS::OSS_HEADERS 等参数，其中ALIOSS::OSS_HEADERS中可以设置的参数如下：
					x-oss-copy-source-if-match 如果源Object的ETAG值和用户提供的ETAG相等，则执行拷贝操作；
					否则返回412 HTTP错误码（预处理失败）
					x-oss-copy-source-if-none-match 如果源Object自从用户指定的时间以后就没有被修改过，则执行拷贝操作；
					否则返回412 HTTP错误码（预处理失败）
					x-oss-copy-source-if-unmodified-since 如果传入参数中的时间等于或者晚于文件实际修改时间，则正常传输文件，
					并返回200 OK；否则返回412 precondition failed错误
					x-oss-copy-source-if-modified-since 如果源Object自从用户指定的时间以后被修改过，则执行拷贝操作；
					否则返回412 HTTP错误码（预处理失败）
				isFullCopy 是否启用全部拷贝，如果设置为true，则无需设置startRange和endRange 
				startRange 如果isFullCopy为false，该参数有效，指的是拷贝来源object的起始位置
				endRange  如果isFullCopy为false，该参数有效，指的是拷贝开源object的终止位置

		`

 * 响应结果
		`
        将结果Response转换成array得到，下同

            Array
            (
                [success] => 1
                [status] => 200
                [header] => Array
                    (
                        [date] => Thu, 06 Aug 2015 18:13:59 GMT
                        [content-type] => application/xml
                        [content-length] => 180
                        [connection] => keep-alive
                        [content-range] => bytes 11304368-11534335/11534336
                        [etag] => "E95C28888F15B92B9C49C9ECEC53C958"
                        [server] => AliyunOSS
                        [x-oss-bucket-version] => 1438864637
                        [x-oss-request-id] => 55C3A3E79646C3C03F40EA5E
                    )

                [body] => Array
                    (
                        [CopyPartResult] => Array
                            (
                                [LastModified] => 2015-08-06T18:13:59.000Z
                                [ETag] => "E95C28888F15B92B9C49C9ECEC53C958"
                            )

                    )

            )
		`
* 获取Part列表
 * 示例代码

		`

		    $bucket_name = 'bucket name';
		    $object_name = 'object name';
		    $upload_id = 'upload id';
			$options = null;
		    $response = $client->list_parts($bucket_name,$object_name, $upload_id,$options);

		` 


 * 参数说明

		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$upload_id 必选参数，上传part对应的multipart uploads Id
			$options 可选参数，无需设置

		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-type] => application/xml
			        [content-length] => 584
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 11:35:40 GMT
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5595220C031C87E546162F44
			    )
			
			    [body] => Array(
			        [ListPartsResult] => Array(
			            [Bucket] => common-bucket
			            [Key] => multipart-upload-1435836813
			            [UploadId] => B4D4B89F8B064A3D835D83D7805B49F3
			            [StorageClass] => Standard
			            [PartNumberMarker] => 0
			            [NextPartNumberMarker] => 1
			            [MaxParts] => 1000
			            [IsTruncated] => false
			            [Part] => Array(
			                [PartNumber] => 1
			                [LastModified] => 2015-07-02T11:35:40.000Z
			                [ETag] => "3AE3AD480200A26738F10CBF2FFBE8B6"
			                [Size] => 5242880
			            )
			        )
			    )
			)

		`


* 获取mulipartUpload列表
 * 示例代码
 
		`

		    $bucket_name = 'bucket name';
		    $options = array(
		        'delimiter' => '/',
		        'max-uploads' => 2,
		        'key-marker' => '',
		        'prefix' => '',
		        'upload-id-marker' => ''
		    );
		    $response = $client->list_multipart_uploads($bucket_name,$options);


		`


 * 参数说明

		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，如果需要设置，可以设置如下参数
				delimiter 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素——
				CommonPrefixes max-uploads  限定此次返回Multipart Uploads事件的最大数目，如果不设定，默认为1000，max-keys取值不能大于1000
				key-marker 与upload-id-marker参数一同使用来指定返回结果的起始位置。 l 如果upload-id-marker参数未设置，查询结果中包含：
				所有Object名字的字典序大于key-marker参数值的Multipart事件。 l 如果upload-id-marker参数被设置，查询结果中包含：
				所有Object名字的字典序大于key-marker参数值的Multipart事件和Object名字等于key-marker参数值，但是Upload ID比upload-id-marker
				参数值大的Multipart Uploads事件
				prefix 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix
				upload-id-marker 与key-marker参数一同使用来指定返回结果的起始位置。 l 如果key-marker参数未设置，则OSS忽略upload-id-marker参数。 
				 如果key-marker参数被设置，查询结果中包含：所有Object名字的字典序大于key-marker参数值的Multipart事件和Object名字等于key-marker
				参数值，但是Upload ID比upload-id-marker参数值大的Multipart Uploads事件

		`		


 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-type] => application/xml
			        [content-length] => 876
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 12:01:50 GMT
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5595282E031C87E546163301
			    )
			
			    [body] => Array(
			        [ListMultipartUploadsResult] => Array(
			            [Bucket] => common-bucket
			            [KeyMarker] => 
			            [UploadIdMarker] => 
			            [NextKeyMarker] => multipart-upload-1435835648
			            [NextUploadIdMarker] => 5C79DDEC71DE478AA4AD9E9AA8BFE6DE
			            [Delimiter] => /
			            [Prefix] => 
			            [MaxUploads] => 2
			            [IsTruncated] => true
			            [Upload] => Array(
			                [0] => Array(
			                    [Key] => multipart-upload-1435835395
			                    [UploadId] => 799C914C0EC3448BAC126849A1B1D6D0
			                    [StorageClass] => Standard
			                    [Initiated] => 2015-07-02T11:09:55.000Z
			                )
			
			                [1] => Array(
			                    [Key] => multipart-upload-1435835648
			                    [UploadId] => 5C79DDEC71DE478AA4AD9E9AA8BFE6DE
			                    [StorageClass] => Standard
			                    [Initiated] => 2015-07-02T11:14:08.000Z
			                )
			            )
			        )
			    )
			)

		`


* 终止multipartUpload
 * 示例代码
 
		`

		    $bucket_name = 'bucket name';
		    $object_name = 'object name';
		    $upload_id = 'upload id';
			$options = null;
		    $response = $client->abort_multipart_upload($bucket_name,$object_name,$upload_id,$options);


		`


 * 参数说明
 * 
 		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$upload_id 必选参数，上传part对应的multipart uploads Id
			$options 可选参数，无需设置

		`
		


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 204
			    [header] => Array(
			        [content-length] => 0
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 11:53:52 GMT
			        [server] => AliyunOSS
			        [x-oss-request-id] => 55952650031C87E5461631E7
			    )
			
			    [body] => 
			)

		`
	

* 完成multipartUpload
 * 示例代码
 
		`

		    $bucket_name = 'bucket name';
		    $object_name = 'object name';
		    $upload_id = 'upload id';
		
		    $upload_parts = array();
		    $upload_parts[] = array(
		        'PartNumber' => 1,
		        'ETag' => '3AE3AD480200A26738F10CBF2FFBE8B6'
		    );
			$options = null;
			$response = $client->complete_multipart_upload($bucket_name,$object_name,$upload_id,$upload_parts,$options);

		`

 * 参数说明
 

		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$upload_id 必选参数，上传part对应的multipart uploads Id
			$upload_parts 包含part的数组，其中必须包含PartNumber和Etag
			$options 可选参数，无需设置

		`


 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [content-type] => application/xml
			        [content-length] => 331
			        [connection] => close
			        [date] => Thu, 02 Jul 2015 11:35:40 GMT
			        [etag] => "003B6AEB546001A97D838E411025239A-1"
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5595220C031C87E546162F45
			        [x-oss-server-side-encryption] => AES256
			    )
			
			    [body] => Array(
			        [CompleteMultipartUploadResult] => Array(
			            [Location] => http://common-bucket.oss-cn-shanghai.aliyuncs.com/multipart-upload-1435836813
			            [Bucket] => common-bucket
			            [Key] => multipart-upload-1435836813
			            [ETag] => "003B6AEB546001A97D838E411025239A-1"
			        )
			    )
			)

		`
## 生命周期管理(LifeCycle)
* 创建Lifecycle规则
 * 示例代码
 	
		`

    		$bucket_name = 'bucket name';
    		$lifecycle = "
	            <LifecycleConfiguration>
	              <Rule>
	                <ID>DaysRule</ID>
	                <Prefix>days/</Prefix>
	                <Status>Enabled</Status>
	                <Expiration>
	                  <Days>1</Days>
	                </Expiration>
	              </Rule>
	            </LifecycleConfiguration>" ;	
			$options = null;	
			$response = $client->set_bucket_lifecycle($bucket_name,$lifecycle,$options);

		`
	
 * 参数说明
 	
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$lifecycle 必选参数，lifecycle规则，具体请xml元素说明请参阅oss api文档：
			http://docs.aliyun.com/?spm=5176.383663.9.2.1hkILe#/pub/oss/api-reference/bucket&PutBucketLifecycle
			$options 可选参数，无需设置	
			

		`

 * 响应结果
 		
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 06:32:57 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594DB19031C87E5461601D1
			    )
			
			    [body] => 
			)

		`

* 获取lifeCycle规则
 * 示例代码
 	
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->get_bucket_lifecycle($bucket_name,$options);
		`

 * 参数说明
 	
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，无需设置
		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 06:32:57 GMT
			        [content-type] => application/xml
			        [content-length] => 243
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594DB1958DB3AB75216045C
			    )
			
			    [body] => Array(
			        [LifecycleConfiguration] => Array(
			            [Rule] => Array(
			                [ID] => DaysRule
			                [Prefix] => days/
			                [Status] => Enabled
			                [Expiration] => Array(
			                    [Days] => 1
			                )
						)
			        )
			    )
			)

		`

* 删除lifeCycle规则
 * 示例代码
 
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->delete_bucket_lifecycle($bucket_name,$options);
		`	

 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，无需设置
		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 204
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 06:32:58 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594DB1A58DB3AB75216045D
			    )
			
			    [body] => 
			)

		`

## 跨域资源管理(CORS)
* 创建CORS规则
 * 示例代码
		
		`

			$bucket_name = 'bucket name';

			$cors_rule = array();

			$cors_rule[ALIOSS::OSS_CORS_ALLOWED_HEADER]=array("x-oss-test");
			$cors_rule[ALIOSS::OSS_CORS_ALLOWED_METHOD]=array("GET");
			$cors_rule[ALIOSS::OSS_CORS_ALLOWED_ORIGIN]=array("http://www.b.com");
			$cors_rule[ALIOSS::OSS_CORS_EXPOSE_HEADER]=array("x-oss-test1");
			$cors_rule[ALIOSS::OSS_CORS_MAX_AGE_SECONDS] = 10;
			
			$cors_rules=array($cors_rule);
			
			$options = null;
		    $response = $obj->set_bucket_cors($bucket_name, $cors_rules,$options);			
		
		`

 * 参数说明
	
		 `

			$bucket_name 必选参数，必须符合bucket命名规范
			$cors_rules 定义一个cors规则数组，每条规则中需要包含以下元素
				ALIOSS::OSS_CORS_ALLOWED_ORIGIN 必选，指定允许的跨域请求的来源，每条规则最多能有一个"*"符号
				ALIOSS::OSS_CORS_ALLOWED_METHOD 必选，指定允许的跨域请求方法，仅能从GET,PUT,POST,DELETE,HEAD中选择一个或多个
				ALIOSS::OSS_CORS_ALLOWED_HEADER 可选，控制在OPTIONS预取指令中Access-Control-Request-Headers头中指定的header是否允许。
				在Access-Control-Request-Headers中指定的每个header都必须在AllowedHeader中有一条对应的项。允许使用最多一个“*”通配符 
				ALIOSS::OSS_CORS_EXPOSE_HEADER 可选，指定允许用户从应用程序中访问的响应头（例如一个Javascript的XMLHttpRequest对象。）
				不允许使用“*”通配符。
				ALIOSS::OSS_CORS_MAX_AGE_SECONDS 可选，指定浏览器对特定资源的预取（OPTIONS）请求返回结果的缓存时间，单位为秒。
			    一个CORSRule里面最多允许出现一个。
	
			$options 可选参数，无需设置

		`


 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 07:03:29 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594E241031C87E546160697
			    )
			
			    [body] => 
			)
		

		`

* 获取CORS规则
 * 示例代码
 	
		`

			$bucket = 'bucket name';
			$options = null;
			$response = $client->get_bucket_cors($bucket_name,$options);

		`


 * 参数说明
 		
		`

			$bucket_name 必须参数，必须符合bucket命名规范
			$options 可选参数，无需设置	

		` 

 * 响应结果
 	
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 07:03:39 GMT
			        [content-type] => application/xml
			        [content-length] => 327
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594E24B58DB3AB752160920
			    )
			
			    [body] => Array(
			        [CORSConfiguration] => Array(
			            [CORSRule] => Array(
			                [AllowedOrigin] => http://www.b.com
			                [AllowedMethod] => GET
			                [AllowedHeader] => x-oss-test
			                [ExposeHeader] => x-oss-test1
			                [MaxAgeSeconds] => 10
			            )
			        )
			    )
			)

		`

* 评估是否允许跨域请求
 * 示例代码
 		
		`

		    $bucket_name = 'bucket name';
		    $object_name ='object name';
		    $origin = 'http://www.b.com';
		    $request_method = ALIOSS::OSS_HTTP_GET;
		    $request_headers = 'x-oss-test';
			$options = null;

		    $response = $obj->options_object($bucket_name, $object_name, $origin, $request_method, $request_headers,$options);

		
		`

 * 参数说明
 
		`

			$bucket_name 必须参数，必须符合bucket命名规范
			$object_name 必选参数，必须符合object命名规范
			$origin 必选参数，请求来源域，用来标示跨域请求
			$request_method 必选参数，表示在实际请求中将会用到的方法
			$request_headers 必选参数，表示在实际请求中会用到的除了简单头部之外的headers
			$options 可选参数，无需设置
			

		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 07:03:39 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594E24B031C87E5461606A1
			    )
			
			    [body] => 
			)

		`


* 删除CORS规则
 * 示例代码
 
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->delete_bucket_cors($bucket_name,$options);
		
		`
 * 参数说明

		`
		
			$bucket_name 必须参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array
			(
			    [status] => 204
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 07:03:39 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594E24B031C87E5461606A2
			    )
			
			    [body] => 
			)

		`

## 静态网站托管(WebSite)
* 设置WebSite
 * 示例代码

		`

			$bucket_name = 'bucket name';
			$index_document = 'index.html';
		    $error_document = 'error.html';	
			$options = null;	
			$response = $client->set_bucket_website($bucket_name,$index_document,$error_document,$options);

		`

 * 参数说明
 		
		`

			$bucket_name 必须参数，必须符合bucket命名规范
			$index_document 必选参数，开启website功能，必须设置index_document
			$error_document 可选参数，自行决定在开启website功能时，是否设置error_document
			$options 可选参数，无需设置	

		`
		
 * 响应结果
 	
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 02:39:23 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594A45B58DB3AB75215E239
			    )
			
			    [body] => 
			)			
	
		`

* 获取WebSite设置
 * 示例代码
 		
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->get_bucket_website($bucket_name,$options);
		
		`

 * 参数说明

		`
		
			$bucket_name 必须参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`

 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 02:39:24 GMT
			        [content-type] => application/xml
			        [content-length] => 218
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594A45C031C87E54615DF98
			    )
			
			    [body] => Array(
			        [WebsiteConfiguration] => Array(
			            [IndexDocument] => Array(
			                [Suffix] => index.html
			            )
			
			           [ErrorDocument] => Array(
			                [Key] => error.html
			            )
					)
				)
			)	
		

		`

* 删除WebSite
 * 示例代码

		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->delete_bucket_website($bucket_name,$options);
		
		`

 * 参数说明

		`
		
			$bucket_name 必须参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`		 

 * 响应结果
 
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 204
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 02:39:24 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594A45C031C87E54615DF99
			    )
			    [body] => 
			)			
	
		`	

## 日志管理(Logging)
* 设置Logging
 * 示例代码
 
		`

			$bucket_name = "bucket name";
			$target_bucket_name = "logging target bucket";
			$target_prefix = "logging file prefix";
			$options = null;
			$response = $client->set_bucket_logging($bucket_name,$target_bucket_name,$target_prefix,$options);

		`

 * 参数说明
 
		`
	
			$bucket_name 必须参数，必须符合bucket命名规范；且必须是属于owner的存在的bucket
			$target_bucket_name 必须参数，日志保存的目标bucket，且必须和要记录日志的bucket在同一集群
			$target_prefix 可选参数，如果设置，日志文件的名称为 $target_prefix + oss日志规范命名
			$options 可选参数，无需设置
		`

 * 响应结果
 		
		`
        将结果Response转换成array得到，下同
		
			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 01:59:06 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 55949AEA031C87E54615D996
			    )
			
			    [body] => 
			)
	
		`		

* 获取logging设置
 * 示例代码
 		
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->get_bucket_logging($bucket_name,$options);
		`
		
 * 参数说明
 		
		`
		
			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`

 * 响应结果
 		
		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 02:14:09 GMT
			        [content-length] => 235
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 55949E7158DB3AB75215DE78
			    )
			
			    [body] => Array(
			        [BucketLoggingStatus] => Array(
			            [LoggingEnabled] => Array(
			                [TargetBucket] => a-00000000000000000003
			                [TargetPrefix] => common-bucket-logging-
			            )
			        )
			    )
			)

		`
	
* 删除Logging
 * 示例代码
 	
		`

			$bucket_name = "bucket name";
			$options = null;
			$response = $client->get_bucket_logging($bucket_name,$options);			
		
		`				

 * 参数说明
 		
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`

 * 响应结果
 	
		`
        将结果Response转换成array得到，下同
		
			Array(
			    [status] => 204
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 02:29:12 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594A1F858DB3AB75215E0C4
			    )
			
			    [body] => 
			)

		`	


## 防盗链(Referer)
* 设置Referer防盗链
 * 示例代码

		`
	
		    $bucket_name = 'bucket name';
		    $is_allow_empty_referer = true;
		    $referer_list = array(
		        'http://aliyun.com',
		        'http://sina.com.cn'
		    );
			$options = null;
			$response = $client->set_bucket_referer($bucket_name,$is_allow_empty_referer,$referer_list,$options);

		`

 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$is_allow_empty_referer 必选参数，是否允许空referer，默认为true
			$referer_list 可选参数，允许的refer白名单列表，注意每条记录均需以http://开头
			$options 可选参数，无需设置		
			

		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 03:30:46 GMT
			        [content-length] => 0
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594B06658DB3AB75215E9EC
			    )
			
			    [body] => 
			)

		`

* 获取Referer设置
 * 示例代码
 
		`
	
		    $bucket_name = 'bucket name';
			$options = null;
			$response = $client->get_bucket_referer($bucket_name,$options);

		`

 * 参数说明
 
		`

			$bucket_name 必选参数，必须符合bucket命名规范
			$options 可选参数，无需设置

		`

 * 响应结果

		`
        将结果Response转换成array得到，下同

			Array(
			    [status] => 200
			    [header] => Array(
			        [date] => Thu, 02 Jul 2015 03:30:46 GMT
			        [content-type] => application/xml
			        [content-length] => 248
			        [connection] => close
			        [server] => AliyunOSS
			        [x-oss-request-id] => 5594B06658DB3AB75215E9F2
			    )
			
			    [body] => Array(
			        [RefererConfiguration] => Array(
			            [AllowEmptyReferer] => true
			            [RefererList] => Array(
			                [Referer] => Array(
			                    [0] => http://aliyun.com
			                    [1] => http://sina.com.cn
			                )
						)
					)
			    )
			)

		`

## URL签名操作
* 获取Get签名URL
 * 示例代码
 	
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$timeout = 3600;
			$options = null;
			$signed_url = $client->get_sign_url($bucket_name,$object_name,$timeout,$options);
		`

 * 参数说明
 	
		`

			$bucket_name 必选参数，参数需要符合bucket命名规范
			$object_name 必选参数，参数需要符合object命名规范
			$timeout 必选参数，过期时间
			$options 可选参数，无需设置

		`		


 * 响应结果
 		
		`

			http://common-bucket.oss-cn-shanghai.aliyuncs.com/my_get_file.log?OSSAccessKeyId=ACSb***&Expires=1435820652&
			Signature=AW5z87zmaLulEmvMzf6ZOUrVboE%3D

		`

* 获取Get或Put签名URL
 * 示例代码
 	
		`

			$bucket_name = 'bucket name';
			$object_name = 'object name';
			$timeout = 3600;
			$method = ALIOSS::OSS_HTTP_GET;
			$options = null;
			$signed_url = $client->get_sign_url($bucket_name,$object_name,$timeout,$method,$options);
		`

 * 参数说明
 	
		`

			$bucket_name 必选参数，参数需要符合bucket命名规范
			$object_name 必选参数，参数需要符合object命名规范
			$timeout 必选参数，过期时间
			$method 必选参数，方法类型，目前支持GET、PUT
			$options 可选参数，无需设置

		`		


 * 响应结果
 		
		`

			http://common-bucket.oss-cn-shanghai.aliyuncs.com/my_get_file.log?OSSAccessKeyId=ACSb***&Expires=1435820652&
			Signature=AW5z87zmaLulEmvMzf6ZOUrVboE%3D

		`
 
