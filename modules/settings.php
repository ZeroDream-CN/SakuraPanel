<?php
namespace SakuraPanel;

use SakuraPanel;

$page_title = "站点设置";
$um = new SakuraPanel\UserManager();
$nm = new SakuraPanel\NodeManager();
$pm = new SakuraPanel\ProxyManager();
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs || $rs['group'] !== "admin") {
	exit("<script>location='?page=panel';</script>");
}

$broadcast = SakuraPanel\Settings::get("broadcast");
$helpinfo  = SakuraPanel\Settings::get("helpinfo");
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.infotable th {
	width: 30%;
}
#broadcast, #helpinfo {
	width: 100%;
	height: 256px;
	max-width: 100%;
	min-width: 100%;
	min-height: 256px;
}
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">更改网站的相关设置</small></h1></div>
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
                            <h3 class="card-title">编辑公告</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p style="margin-top: -16px;">在此处填写公告内容，支持 Markdown 语法。</p>
						<textarea class="form-control" id="broadcast"><?php echo $broadcast; ?></textarea>
					</div>
					<div class="card-footer">
						<button type="button" class="btn btn-default" onclick="preview(broadcast.value)">预览更改</button>
						<button type="button" class="btn btn-primary float-right" onclick="saveBroadcast()">保存修改</button>
					</div>
                </div>
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">编辑帮助</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p style="margin-top: -16px;">在此处填写帮助内容，让用户更好的了解如何使用，支持 Markdown 语法。</p>
						<textarea class="form-control" id="helpinfo"><?php echo $helpinfo; ?></textarea>
                    </div>
					<div class="card-footer">
						<button type="button" class="btn btn-default" onclick="preview(helpinfo.value)">预览更改</button>
						<button type="button" class="btn btn-primary float-right" onclick="saveHelpInfo()">保存修改</button>
					</div>
                </div>
			</div>
			<div class="col-lg-4">
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">站点信息</h3>
                        </div>
                    </div>
                    <div class="card-body fix-text table-responsive p-0">
						<table class="table table-striped table-valign-middle infotable" style="width: 100%;font-size: 15px;margin-top: 0px;margin-bottom: 0px;">
							<tr>
								<th>版本</th>
								<td><?php echo SakuraPanel\Utils::PANEL_VERSION; ?></td>
							</tr>
							<tr>
								<th>服务器程序</th>
								<td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
							</tr>
							<tr>
								<th>运行模式</th>
								<td><?php echo php_sapi_name(); ?></td>
							</tr>
							<tr>
								<th>授权类型</th>
								<td>免费版 (仅限非商业使用)</td>
							</tr>
							<tr>
								<th>注册用户数</th>
								<td><?php echo $um->getTotalUsers(); ?></td>
							</tr>
							<tr>
								<th>服务器节点数</th>
								<td><?php echo $nm->getTotalNodes(); ?></td>
							</tr>
							<tr>
								<th>映射隧道数</th>
								<td><?php echo $pm->getTotalProxies(); ?></td>
							</tr>
							<tr>
								<th>更新检查</th>
								<td id="updateinfo">正在检查更新...</td>
							</tr>
						</table>
						<p style="padding: 12px;">&copy; 2019-<?php echo date("Y"); ?> SakuraPanel</p>
                    </div>
                </div>
            </div>
		</div>
	</div>
</div>
<div class="modal fade" id="modal-default" style="display: none;" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="msg-title"></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body" id="msg-body"></div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal">确定</button></div>
        </div>
    </div>
</div>
<script type="text/javascript">
var csrf_token = "<?php echo $_SESSION['token']; ?>";
function alertMessage(title, body) {
	$("#msg-title").html(title);
	$("#msg-body").html(body);
	$("#modal-default").modal('toggle');
}
function saveBroadcast() {
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?action=updatebroadcast&page=panel&module=settings&csrf=" + csrf_token,
		async:true,
		data: {
			data: $("#broadcast").val()
		},
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			alert(htmlobj.responseText);
			return;
		}
	});
}
function saveHelpInfo() {
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?action=updatehelpinfo&page=panel&module=settings&csrf=" + csrf_token,
		async:true,
		data: {
			data: $("#helpinfo").val()
		},
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			alert(htmlobj.responseText);
			return;
		}
	});
}
function preview(data) {
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?action=preview&page=panel&module=settings&csrf=" + csrf_token,
		async:true,
		data: {
			data: data
		},
		error: function() {
			alertMessage("发生错误", htmlobj.responseText);
			return;
		},
		success: function() {
			alertMessage("预览更改", htmlobj.responseText);
			return;
		}
	});
}
function checkUpdate() {
	$("#updateinfo").html("正在检查更新...");
	var htmlobj = $.ajax({
		type: 'GET',
		url: "https://cdn.zerodream.net/panel/update.php?s=SakuraPanel&version=<?php echo urlencode(SakuraPanel\Utils::PANEL_VERSION); ?>",
		async:true,
		error: function() {
			$("#updateinfo").html("检查更新出错：" + htmlobj.responseText);
			return;
		},
		success: function() {
			try {
				var json = JSON.parse(htmlobj.responseText);
				$("#updateinfo").html("最新版本：" + json.version + "<br>更新内容：" + json.message);
			} catch(e) {
				
			}
			return;
		}
	});
}
window.onload = checkUpdate;
</script>