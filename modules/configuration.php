<?php
namespace SakuraPanel;

use SakuraPanel;

$pm = new SakuraPanel\ProxyManager();
$page_title = "配置文件";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}

$sel_server = isset($_GET['server']) && preg_match("/^[0-9]+$/", $_GET['server']) ? Intval($_GET['server']) : 0;
if($sel_server <= 0) {
	$sel_server = 1;
}
$ss = Database::toArray(Database::search("nodes", Array("group" => "{$rs['group']};", "status" => "200")));
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.sub-heading {
	width: calc(100% - 16px);
    height: 0!important;
    border-top: 1px solid #e9f1f1!important;
    text-align: center!important;
    margin-top: 32px!important;
    margin-bottom: 40px!important;
	margin-left: 7px;
}
.sub-heading span {
    display: inline-block;
    position: relative;
    padding: 0 17px;
    top: -11px;
    font-size: 16px;
    color: #058;
    background-color: #fff;
}
</style>
<link href="assets/configuration/prettify.css" rel="stylesheet">
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">获取用于客户端的配置文件</small></h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="?">主页</a></li>
                    <li class="breadcrumb-item active"><?php echo $page_title; ?></li></ol>
            </div>
        </div>
	</div>
</div>
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">配置文件获取</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p><b>选择服务器</b></p>
						<p><select class="form-control" id="server" <?php echo count($ss) == 0 ? "disabled" : ""; ?>>
							<?php
							foreach($ss as $si) {
								$selected = $sel_server == $si[0] ? "selected" : "";
								echo "<option value='{$si[0]}' {$selected}>{$si[1]} ({$si[3]})</option>";
							}
							if(count($ss) == 0) {
								echo "<option>没有可用的服务器</option>";
							}
							?>
						</select></p>
						<p><b>配置文件内容</b></p>
						<pre class="prettyprint linenums"><?php echo count($ss) !== 0 ? $pm->getUserProxiesConfig($_SESSION['user'], $sel_server) : "当前所有服务器都不可用，请联系管理员。"; ?></pre>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">配置文件说明</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p>每次创建完映射或删除了映射之后配置文件都会发生变化，请在变更后及时更新您的配置文件。</p>
						<p class='text-danger'>请勿泄露配置文件中 user 字段的内容，否则他人可以登录您的账号，截图注意打码。</p>
						<p>不过，如果真的泄露了，可以通过修改密码来解决，User 字段的内容也会随之更新。</p>
						<div class="sub-heading">
							<span>配置安装方法</span>
						</div>
						<p><ol>
							<li>将左侧的内容复制。</li>
							<li>在客户端的同级目录创建一个文本文档，命名为 frpc.ini 。</li>
							<li>使用 Notepad++ 等专业的文本编辑器打开它</li>
							<li>将复制的内容粘贴到里面并保存。</li>
						</ol></p>
						<div class="sub-heading">
							<span>客户端启动方法</span>
						</div>
						<p><ol>
							<li>按照上面的方法储存好你的配置文件。</li>
							<li>在客户端的目录里按住 Shift + 鼠标右键。</li>
							<li>点击 “在此处打开命令提示符” 或 “在此处打开 PowerShell”。</li>
							<li>输入命令 <code>frpc.exe -c frpc.ini</code> 并按下回车启动。</li>
							<li>保持命令提示符窗口打开，不要关闭它，否则映射会中断。</li>
						</ol></p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript" src="assets/configuration/prettify.js"></script>
<script type="text/javascript">
prettyPrint();
window.onload = function() {
	$('#server').change(function() {
		location = "/?page=panel&module=configuration&server=" + $(this).children('option:selected').val();
	});
}
</script>