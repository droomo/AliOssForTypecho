# Typecho插件AliOssForTypecho

### 介绍
用于将Typecho上传的附件保存到阿里云的OSS。
最后更新于2020年6月。

### 特性
1. 可以选择只保存到OSS，或同时保存到OSS和服务器，方便维护；
2. 实现同名文件上传时自动重命名，如在OSS或服务器上已经存在名为 `test.png` 的图片，若再次上传 `test.png`，新图片会重命名成 `test(1).png`；
3. 记录上传文件时的错误日志，再也不用为莫名其妙的上传失败或者上传时毫无反应懵逼了。
4. 可以自定义CDN地址、OSS储存前缀、OSS连接方式（内网、外网）；
5. 可以使用自定义样式。借鉴 [DefectingCat](https://github.com/DefectingCat/AliOssForTypecho-)
