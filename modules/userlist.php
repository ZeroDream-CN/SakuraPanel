<?php
namespace SakuraPanel;

use SakuraPanel;

$page_title = "用户列表";
$um = new SakuraPanel\UserManager();
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs || $rs['group'] !== "admin") {
	exit("<script>location='?page=panel';</script>");
}

if(isset($_GET['getinfo']) && preg_match("/^[0-9]{1,10}$/", $_GET['getinfo'])) {
	SakuraPanel\Utils::checkCsrf();
	$rs = Database::querySingleLine("users", Array("id" => $_GET['getinfo']));
	if($rs) {
		$lm = $um->getLimit($rs['username']);
		$inbound  = $lm['type'] == 1 ? $lm['inbound'] : "";
		$outbound = $lm['type'] == 1 ? $lm['outbound'] : "";
		ob_clean();
		exit(json_encode(Array(
			"id"       => $rs['id'],
			"username" => $rs['username'],
			"traffic"  => $rs['traffic'],
			"proxies"  => $rs['proxies'],
			"inbound"  => $inbound,
			"outbound" => $outbound,
			"group"    => $rs['group'],
			"status"   => $rs['status']
		)));
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
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">管理本站点的用户</small></h1></div>
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
                            <h3 class="card-title">用户列表</h3>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
						<table class="table table-striped table-valign-middle" style="width: 100%;font-size: 15px;">
							<tr>
								<th class='text-center' nowrap>ID</th>
								<th class='text-center' nowrap>用户名</th>
								<th class='text-center' nowrap>邮箱</th>
								<th class='text-center' nowrap>流量</th>
								<th class='text-center' nowrap>隧道</th>
								<th class='text-center' nowrap>用户组</th>
								<th class='text-center' nowrap>注册时间</th>
								<th class='text-center' nowrap>状态</th>
								<th class='text-center' nowrap>操作</th>
							</tr>
							<?php
							$spage          = isset($_GET['p']) && preg_match("/^[0-9]{1,9}$/", $_GET['p']) && Intval($_GET['p']) > 0 ? (Intval($_GET['p'])) : 1;
							$_GET['search'] = isset($_GET['search']) ? Database::escape($_GET['search']) : "";
							$_GET['p']      = isset($_GET['p']) && preg_match("/^[0-9]{1,9}$/", $_GET['p']) && Intval($_GET['p']) > 0 ? (Intval($_GET['p']) - 1) * 10 : "";
							$mainSQL = "SELECT * FROM `users` ";
							$mainSQL .= (isset($_GET['search']) && !empty($_GET['search'])) ? "WHERE POSITION('{$_GET['search']}' IN `username`) OR POSITION('{$_GET['search']}' IN `email`) " : "";
							$mainSQL .= (isset($_GET['p']) && !empty($_GET['p'])) ? "LIMIT {$_GET['p']},11" : "LIMIT 0,11";
							$rs = Database::toArray(Database::query("users", $mainSQL, true));
							$i = 0;
							foreach($rs as $user) {
								$i++;
								if($i > 10) break;
								$traffic = round($user[4] / 1024, 2) . "GB";
								$regtime = date("Y-m-d", $user[7]);
								$statuss = Array(0 => "正常", 1 => "已封号");
								$status  = $statuss[$user[8]] ?? "未知";
								echo "<tr>
								<td class='text-center' nowrap>{$user[0]}</td>
								<td class='text-center' nowrap>{$user[1]}</td>
								<td class='text-center' nowrap>{$user[3]}</td>
								<td class='text-center' nowrap>{$traffic}</td>
								<td class='text-center' nowrap>{$user[5]}</td>
								<td class='text-center' nowrap>{$user[6]}</td>
								<td class='text-center' nowrap>{$regtime}</td>
								<td class='text-center' nowrap>{$status}</td>
								<td class='text-center' nowrap><a href='javascript:edit({$user[0]})'>[编辑]</a></td>
								";
							}
							?>
						</table>
						<?php
						if($i == 0) {
							echo "<p class='text-center'>没有找到符合条件的结果</p>";
						}
						?>
						<div class="text-right page-num">
						<span>当前在第 <?php echo $spage; ?> 页&nbsp;&nbsp;</span>
						<?php
						$search = isset($_GET['search']) ? "&search=" . urlencode($_GET['search']) : "";
						$fpage = $spage - 1;
						$npage = $spage + 1;
						if($i > 10) {
							if(isset($_GET['p']) && Intval($_GET['p']) > 1) {
								echo "<a href='?page=panel&module=userlist{$search}'><button class='btn btn-default'><i class='fa fa-home'></i></button></a>&nbsp;&nbsp;";
								echo "<a href='?page=panel&module=userlist{$search}&p={$fpage}'><button class='btn btn-default'><i class='fa fa-angle-left'></i></button></a>&nbsp;&nbsp;";
							}
							echo "<a href='?page=panel&module=userlist{$search}&p={$npage}'><button class='btn btn-default'><i class='fa fa-angle-right'></i></button></a>";
						} else {
							if(isset($_GET['p']) && Intval($_GET['p']) > 1) {
								echo "<a href='?page=panel&module=userlist{$search}'><button class='btn btn-default'><i class='fa fa-home'></i></button></a>&nbsp;&nbsp;";
								echo "<a href='?page=panel&module=userlist{$search}&p={$fpage}'><button class='btn btn-default'><i class='fa fa-angle-left'></i></button></a>";
							}
						}
						?></div>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">修改用户信息</h3>
                        </div>
                    </div>
                    <div class="card-body">
						<p id="statusmsg">点击左侧用户列表的编辑按钮来编辑用户信息</p>
						<div class="sub-heading">
							<span>搜索用户</span>
						</div>
						<p>可输入用户名、邮箱来进行搜索</p>
						<div class="input-group">
							<input type="text" class="form-control" id="searchdata">
							<span class="input-group-append">
								<button type="button" class="btn btn-info" onclick="search()"><i class="fas fa-search"></i></button>
							</span>
						</div>
						<div class="sub-heading">
							<span>映射设置</span>
						</div>
						<p>此部分设置如果留空将会使用用户组的设置进行覆盖。例如您需要将某个用户设置为 VIP，同时更新该用户的限速、流量等信息至 VIP 的设置，请先清空此部分输入框内容。</p>
						<p><b>流量设置</b>&nbsp;&nbsp;<small>单位 MB，修改后即时生效。</small></p>
						<p><input type="number" class="form-control" id="traffic"></input></p>
						<p><b>隧道数量</b>&nbsp;&nbsp;<small>用户最多可以添加的隧道数量</small></p>
						<p><input type="number" class="form-control" id="proxies"></input></p>
						<p><b>最大上传</b>&nbsp;&nbsp;<small>单位 KB/s，留空则继承组设定</small></p>
						<p><input type="number" class="form-control" id="inbound"></input></p>
						<p><b>最大下行</b>&nbsp;&nbsp;<small>单位 KB/s，留空则继承组设定</small></p>
						<p><input type="number" class="form-control" id="outbound"></input></p>
						<div class="sub-heading">
							<span>权限设置</span>
						</div>
						<p><b>用户组</b>&nbsp;&nbsp;<small>选择需要将用户分配到的用户组</small></p>
						<p><select class="form-control" id="group">
							<?php
							$gs = Database::toArray(Database::query("groups", "SELECT * FROM `groups`", true));
							foreach($gs as $gi) {
								echo "<option value='{$gi[1]}'>{$gi[2]}</option>";
							}
							?>
							<option value="admin">管理员</option>
						</select></p>
						<p><b>用户状态</b>&nbsp;&nbsp;<small>切换用户的状态</small></p>
						<select class="form-control" id="status">
							<option value="0">正常</option>
							<option value="1">封号</option>
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
var userid = "";
function search() {
	window.location = window.location.href + '&search=' + encodeURIComponent($(searchdata).val());
}
function edit(id) {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=userlist&getinfo=" + id + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			try {
				var json = JSON.parse(htmlobj.responseText);
				userid = json.id;
				$("#traffic").val(json.traffic);
				$("#proxies").val(json.proxies);
				$("#inbound").val(json.inbound);
				$("#outbound").val(json.outbound);
				$("#group").val(json.group);
				$("#status").val(json.status);
				$("#statusmsg").html("正在编辑用户 " + json.username + " 的设置");
			} catch(e) {
				alert("错误：无法解析服务器返回的数据");
			}
			return;
		}
	});
}
function save() {
	if(userid == "") {
		alert("您未编辑任何用户信息。");
		return;
	}
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?action=updateuser&page=panel&module=userlist&csrf=" + csrf_token,
		async:true,
		data: {
			id: userid,
			traffic: $("#traffic").val(),
			proxies: $("#proxies").val(),
			inbound: $("#inbound").val(),
			outbound: $("#outbound").val(),
			group: $("#group").val(),
			status: $("#status").val()
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