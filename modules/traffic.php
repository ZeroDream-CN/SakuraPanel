<?php
namespace SakuraPanel;

use SakuraPanel;

$page_title = "流量统计";
$um = new SakuraPanel\UserManager();
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs || $rs['group'] !== "admin") {
	exit("<script>location='?page=panel';</script>");
}

if(isset($_GET['getinfo']) && preg_match("/^[0-9]{1,10}$/", $_GET['getinfo'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	$nm = new SakuraPanel\NodeManager();
	$rs = $nm->getNodeInfo($_GET['getinfo']);
	if(is_array($rs)) {
		$hs = SakuraPanel\Utils::http("http://admin:{$rs['admin_pass']}@{$rs['ip']}:{$rs['admin_port']}/api/serverinfo");
		if(isset($hs['status']) && $hs['status'] == 200) {
			$js = json_decode($hs['body'], true);
			$tf_in  = SakuraPanel\Utils::getFormatTraffic($js['total_traffic_in']);
			$tf_out = SakuraPanel\Utils::getFormatTraffic($js['total_traffic_out']);
			echo <<<EOF
<h4>{$rs['name']} <small>节点信息</small></h4>
<hr>
<table style="width: 100%;" class="sinfotable">
	<tr><th>服务端版本</th><td>{$js['version']}</td></tr>
	<tr><th>监听端口</th><td>{$js['bind_port']}</td></tr>
	<tr><th>UDP 监听端口</th><td>{$js['bind_udp_port']}</td></tr>
	<tr><th>HTTP 监听端口</th><td>{$js['vhost_http_port']}</td></tr>
	<tr><th>HTTPS 监听端口</th><td>{$js['vhost_https_port']}</td></tr>
	<tr><th>总共入网流量</th><td>{$tf_in}</td></tr>
	<tr><th>总共入网流量</th><td>{$tf_out}</td></tr>
	<tr><th>连接数量</th><td>{$js['cur_conns']}</td></tr>
	<tr><th>客户端数量</th><td>{$js['client_counts']}</td></tr>
</table>
EOF;
			exit;
		} else {
			Header("HTTP/1.1 404 Not Found");
			exit("无法连接至服务器，错误代码：{$hs['status']}");
		}
	} else {
		exit("未找到该隧道");
	}
}
if(isset($_GET['gettraffic']) && preg_match("/^[0-9]{1,10}$/", $_GET['gettraffic']) && in_array($_GET['type'], ["tcp", "udp", "http", "https", "stcp"])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	$um = new SakuraPanel\UserManager();
	$nm = new SakuraPanel\NodeManager();
	$rs = $nm->getNodeInfo($_GET['gettraffic']);
	$tokens = $um->getTokensToUsers();
	if(is_array($rs)) {
		$hs = SakuraPanel\Utils::http("http://admin:{$rs['admin_pass']}@{$rs['ip']}:{$rs['admin_port']}/api/proxy/{$_GET['type']}");
		if(isset($hs['status']) && $hs['status'] == 200) {
			$js = json_decode($hs['body'], true);
			echo '<table class="table table-striped table-valign-middle" style="width: 100%;font-size: 15px;margin-top: 12px;margin-bottom: 0px;">';
			echo '<tr><th>隧道名称</th><th>所属用户</th><th>连接数量</th><th>今日流量 (↓/↑)</th><th>当前状态</th></tr>';
			foreach($js['proxies'] as $proxy) {
				$name = explode(".", $proxy['name']);
				if(count($name) !== 2) continue;
				echo "<tr>";
				echo "<td>{$name[1]}</td>";
				echo "<td>{$tokens[$name[0]]}</td>";
				echo "<td>{$proxy['cur_conns']}</td>";
				$tf_in  = SakuraPanel\Utils::getFormatTraffic($proxy['today_traffic_in']);
				$tf_out = SakuraPanel\Utils::getFormatTraffic($proxy['today_traffic_out']);
				echo "<td>{$tf_in} / {$tf_out}</td>";
				echo "<td>{$proxy['status']}</td>";
				echo "</tr>";
			}
			echo "</table>";
			exit;
		} else {
			Header("HTTP/1.1 404 Not Found");
			exit("无法连接至服务器，错误代码：{$hs['status']}");
		}
	} else {
		exit("未找到该隧道");
	}
}
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.sinfotable th {
	width: 40%;
}
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">查看服务器的流量统计信息</small></h1></div>
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
								<td class='text-center' nowrap><a href='javascript:selectserver({$node[0]})'>[选择]</a></td>
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
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">查看流量排行</h3>
                        </div>
                    </div>
                    <div class="card-body fix-text">
						<p>先点击上面选择一个服务器，然后再点击此处进行查询。</p>
						<p>流量实时查询需要一定时间，请勿频繁点击按钮，否则容易导致服务器卡死。</p>
						<span>选择映射类型：</span>&nbsp;&nbsp;
						<button class="btn btn-default" onclick="gettraffic('tcp')">TCP</button>&nbsp;&nbsp;
						<button class="btn btn-default" onclick="gettraffic('udp')">UDP</button>&nbsp;&nbsp;
						<button class="btn btn-default" onclick="gettraffic('http')">HTTP</button>&nbsp;&nbsp;
						<button class="btn btn-default" onclick="gettraffic('https')">HTTPS</button>&nbsp;&nbsp;
						<button class="btn btn-default" onclick="gettraffic('stcp')">STCP</button>&nbsp;&nbsp;
						<div id="trafficlist" class="table-responsive">
							<!-- placeholder -->
						</div>
                    </div>
                </div>
			</div>
			<div class="col-lg-4">
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">服务器信息</h3>
                        </div>
                    </div>
                    <div class="card-body fix-text" id="serverinfo">
						<p>请选择一个服务器，然后这里会显示信息</p>
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
var nodeid = undefined;
function search() {
	window.location = window.location.href + '&search=' + encodeURIComponent($(searchdata).val());
}
function selectserver(id) {
	$("#serverinfo").html("正在查询...");
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=traffic&getinfo=" + id + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			nodeid = id;
			$("#serverinfo").html(htmlobj.responseText);
			return;
		}
	});
}
function gettraffic(type) {
	if(nodeid == undefined) {
		alert("请先选择一个服务器后再查询流量");
		return;
	}
	$("#trafficlist").html("正在查询...");
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=traffic&gettraffic=" + nodeid + "&type=" + type + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			alert("错误：" + htmlobj.responseText);
			return;
		},
		success: function() {
			$("#trafficlist").html(htmlobj.responseText);
			return;
		}
	});
}
</script>