<?php
namespace SakuraPanel;

use SakuraPanel;

$page_title = "服务器节点";
$um = new SakuraPanel\NodeManager();
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs || $rs['group'] !== "admin") {
	exit("<script>location='?page=panel';</script>");
}

if(isset($_GET['getinfo']) && preg_match("/^[0-9]{1,10}$/", $_GET['getinfo'])) {
	SakuraPanel\Utils::checkCsrf();
	$rs = Database::querySingleLine("nodes", Array("id" => $_GET['getinfo']));
	if($rs) {
		ob_clean();
		exit(json_encode($rs));
	} else {
		ob_clean();
		Header("HTTP/1.1 403");
		exit("未找到用户");
	}
}
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
.page-num {
	margin-right: 8px;
	margin-bottom: 8px;
	margin-top: 8px;
}
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">管理本站点的服务器节点</small></h1></div>
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
                            <h3 class="card-title">服务器节点</h3>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
						<table class="table table-striped table-valign-middle" style="width: 100%;font-size: 15px;">
							<tr>
								<th class='text-center' nowrap>ID</th>
								<th class='text-center' nowrap>名称</th>
								<th class='text-center' nowrap>主机名</th>
								<th class='text-center' nowrap>IP</th>
								<th class='text-center' nowrap>端口</th>
								<th class='text-center' nowrap>状态</th>
								<th class='text-center' nowrap>操作</th>
							</tr>
							<?php
							$rs = Database::toArray(Database::query("users", "SELECT * FROM `nodes`", true));
							$i = 0;
							foreach($rs as $node) {
								$i++;
								$statuss = Array(200 => "正常", 403 => "禁用", 500 => "离线", 401 => "隐藏");
								$status  = $statuss[Intval($node[10])] ?? "未知";
								echo "<tr>
								<td class='text-center' nowrap>{$node[0]}</td>
								<td class='text-center' nowrap>{$node[1]}</td>
								<td class='text-center' nowrap>{$node[3]}</td>
								<td class='text-center' nowrap>{$node[4]}</td>
								<td class='text-center' nowrap>{$node[5]}</td>
								<td class='text-center' nowrap>{$status}</td>
								<td class='text-center' nowrap><a href='javascript:edit({$node[0]})'>[编辑]</a> <a href='javascript:deletenode({$node[0]})'>[删除]</a></td>
								";
							}
							?>
						</table>
						<?php
						if($i == 0) {
							echo "<p class='text-center'>没有找到符合条件的结果</p>";
						}
						?>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">添加或修改节点信息</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p id="statusmsg">点击左侧节点列表的编辑按钮来编辑节点信息</p>
						<div class="sub-heading">
							<span>节点设置</span>
						</div>
						<p><b>节点名称</b>&nbsp;&nbsp;<small>会显示在隧道列表和创建隧道界面</small></p>
						<p><input type="text" class="form-control" id="node_name"></input></p>
						<p><b>节点简介</b>&nbsp;&nbsp;<small>用一句简单的话来介绍这个节点</small></p>
						<p><input type="text" class="form-control" id="node_description"></input></p>
						<p><b>主机名称</b>&nbsp;&nbsp;<small>这里可以是一个域名或者 IP 地址</small></p>
						<p><input type="text" class="form-control" id="node_hostname"></input></p>
						<p><b>IP 地址</b>&nbsp;&nbsp;<small>服务器的 IP 地址，请不要输入域名</small></p>
						<p><input type="text" class="form-control" id="node_ip"></input></p>
						<p><b>节点端口</b>&nbsp;&nbsp;<small>节点的 Frps 服务器运行端口</small></p>
						<p><input type="number" class="form-control" id="node_port"></input></p>
						<p><b>管理端口</b>&nbsp;&nbsp;<small>Frps 管理 API 的端口，用于系统接口</small></p>
						<p><input type="number" class="form-control" id="node_adminport"></input></p>
						<p><b>管理密码</b>&nbsp;&nbsp;<small>Frps 管理 API 的端口，用于系统接口</small></p>
						<p><input type="text" class="form-control" id="node_adminpass"></input></p>
						<p><b>Token</b>&nbsp;&nbsp;<small>用于 Frpc 客户端连接用的 Token</small></p>
						<p><input type="text" class="form-control" id="node_token"></input></p>
						<div class="sub-heading">
							<span>其他设置</span>
						</div>
						<p><b>用户组</b>&nbsp;&nbsp;<small>允许的用户组，用 ; 隔开每个组名</small></p>
						<p><input type="text" class="form-control" id="node_group"></input></p>
						<p><b>节点状态</b>&nbsp;&nbsp;<small>切换节点的状态</small></p>
						<select class="form-control" id="node_status">
							<option value="200">正常</option>
							<option value="401">隐藏</option>
							<option value="403">禁用</option>
							<option value="500">离线</option>
						</select>
					</div>
					<div class="card-footer">
						<button class="btn btn-primary float-sm-right" onclick="save()">保存设置</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
var csrf_token = "<?php echo $_SESSION['token']; ?>";
var nodeid = undefined;
function search() {
	window.location = window.location.href + '&search=' + encodeURIComponent($(searchdata).val());
}
function edit(id) {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=nodes&getinfo=" + id + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			try {
				var json = JSON.parse(htmlobj.responseText);
				nodeid = id;
				$("#node_name").val(json.name);
				$("#node_description").val(json.description);
				$("#node_hostname").val(json.hostname);
				$("#node_ip").val(json.ip);
				$("#node_port").val(json.port);
				$("#node_adminport").val(json.admin_port);
				$("#node_adminpass").val(json.admin_pass);
				$("#node_token").val(json.token);
				$("#node_group").val(json.group);
				$("#node_status").val(json.status);
				$("#statusmsg").html("正在编辑节点 " + json.name + " 的设置");
			} catch(e) {
				alert("错误：无法解析服务器返回的数据");
			}
			return;
		}
	});
}
function deletenode(id) {
	if(!confirm("你确定要删除这个节点吗？此操作不可恢复！\n\n该节点下所有的隧道也会被删除！")) {
		return;
	}
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?action=deletenode&page=panel&module=nodes&csrf=" + csrf_token,
		async:true,
		data: {
			id: id
		},
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			alert(htmlobj.responseText);
			window.location.reload();
			return;
		}
	});
}
function save() {
	var url = "?action=updatenode&page=panel&module=nodes";
	if(nodeid == undefined) {
		nodeid = null;
		url = "?action=addnode&page=panel&module=nodes";
	}
	var htmlobj = $.ajax({
		type: 'POST',
		url: url + "&csrf=" + csrf_token,
		async:true,
		data: {
			id: nodeid,
			name: $("#node_name").val(),
			description: $("#node_description").val(),
			hostname: $("#node_hostname").val(),
			ip: $("#node_ip").val(),
			port: $("#node_port").val(),
			admin_port: $("#node_adminport").val(),
			admin_pass: $("#node_adminpass").val(),
			token: $("#node_token").val(),
			group: $("#node_group").val(),
			status: $("#node_status").val()
		},
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			alert(htmlobj.responseText);
			window.location.reload();
			return;
		}
	});
}
</script>