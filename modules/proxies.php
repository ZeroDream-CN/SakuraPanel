<?php
namespace SakuraPanel;

use SakuraPanel;

include(ROOT . "/core/Parsedown.php");

$markdown = new Parsedown();
$markdown->setSafeMode(true);
$markdown->setBreaksEnabled(true);
$markdown->setUrlsLinked(true);
$page_title = "隧道列表";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));
$pm = new SakuraPanel\ProxyManager();
$nm = new SakuraPanel\NodeManager();
$um = new SakuraPanel\UserManager();

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}

if(isset($_GET['getproxyinfo']) && preg_match("/^[0-9]{1,10}$/", $_GET['getproxyinfo'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	$rs = $pm->getProxyInfo($_GET['getproxyinfo']);
	if($rs) {
		if(isset($rs['username']) && $rs['username'] == $_SESSION['user']) {
			$ns = $nm->getNodeInfo($rs['node']);
			$domain = "";
			$domains = json_decode($rs['domain'], true);
			if($domains && !empty($domains)) {
				for($i = 0;$i < count($domains);$i++) {
					$domain .= $domains[$i];
					if($i < count($domains) - 1) { $domain .= ", "; }
				}
			}
			?>
			<style type="text/css">
			.proxyinfo tr th {
				width: 30%;
				text-align: right;
				padding-right: 16px;
			}
			</style>
			<table class="proxyinfo" style="width: 100%;font-size: 15px;">
				<tr>
					<th>服务器</th>
					<td><?php echo "{$ns['name']} ({$ns['hostname']})"; ?></td>
				</tr>
				<tr>
					<th>隧道 ID</th>
					<td><?php echo $rs['id']; ?></td>
				</tr>
				<tr>
					<th>隧道类型</th>
					<td><?php echo strtoupper($rs['proxy_type']); ?> 映射</td>
				</tr>
				<tr>
					<th>本地地址</th>
					<td><?php echo $rs['local_ip'] == "" ? "127.0.0.1" : $rs['local_ip']; ?></td>
				</tr>
				<tr>
					<th>本地端口</th>
					<td><?php echo $rs['local_port'] == "" ? "80" : $rs['local_port']; ?></td>
				</tr>
				<tr>
					<th>远程端口</th>
					<td><?php echo $rs['remote_port'] == "" ? "无" : $rs['remote_port']; ?></td>
				</tr>
				<tr>
					<th>连接加密</th>
					<td><?php echo $rs['use_encryption'] == "true" ? "启用" : "禁用"; ?></td>
				</tr>
				<tr>
					<th>数据压缩</th>
					<td><?php echo $rs['use_compression'] == "true" ? "启用" : "禁用"; ?></td>
				</tr>
				<tr>
					<th>绑定域名</th>
					<td><?php echo $domain == "" ? "无" : $domain; ?></td>
				</tr>
				<tr>
					<th>URI 绑定</th>
					<td><?php echo $rs['locations'] == "" ? "无" : $rs['locations']; ?></td>
				</tr>
				<tr>
					<th>Host 重写</th>
					<td><?php echo $rs['host_header_rewrite'] == "" ? "无" : $rs['host_header_rewrite']; ?></td>
				</tr>
				<tr>
					<th>连接密码</th>
					<td><?php echo $rs['sk'] == "" ? "无" : $rs['sk']; ?></td>
				</tr>
				<tr>
					<th>X-From-Where</th>
					<td><?php echo $rs['header_X-From-Where'] == "" ? "无" : $rs['header_X-From-Where']; ?></td>
				</tr>
				<tr>
					<th>状态</th>
					<td><?php echo $rs['status'] == "0" ? "启用" : "禁用"; ?></td>
				</tr>
			</table>
			<?php
			exit;
		} else {
			exit("拒绝访问");
		}
	} else {
		exit("未找到该隧道");
	}
}

if(isset($_GET['toggle']) && preg_match("/^[0-9]{1,10}$/", $_GET['toggle'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	$rs = $pm->getProxyInfo($_GET['toggle']);
	if($rs) {
		if(isset($rs['username']) && $rs['username'] == $_SESSION['user']) {
			if($rs['status'] == '2') {
				exit("你的流量已经用完，无法开启隧道，充值或签到获取流量后即可恢复");
			} elseif($rs['status'] == '3') {
				exit("此隧道已经被管理员封禁，无法恢复");
			} else {
				$newStatus = $rs['status'] == "0" ? "1" : "0";
				Database::update("proxies", Array("status" => $newStatus), Array("id" => $_GET['toggle']));
				$nm->closeClient($rs['node'], $um->getUserToken($_SESSION['user']));
				exit("隧道状态更新成功");
			}
		} else {
			exit("拒绝访问");
		}
	} else {
		exit("未找到该隧道");
	}
}

if(isset($_GET['delete']) && preg_match("/^[0-9]{1,10}$/", $_GET['delete'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	$rs = $pm->getProxyInfo($_GET['delete']);
	if($rs) {
		if(isset($rs['username']) && $rs['username'] == $_SESSION['user']) {
			if($rs['status'] == '3') {
				exit("此隧道已经被管理员封禁，无法删除");
			} else {
				Database::delete("proxies", Array("id" => $rs['id']));
				$nm->closeClient($rs['node'], $um->getUserToken($_SESSION['user']));
				exit("隧道删除成功，请刷新页面");
			}
		} else {
			exit("拒绝访问");
		}
	} else {
		exit("未找到该隧道");
	}
}

$use_proxies = $pm->getUserProxies($_SESSION['user']);
$max_proxies = Intval($um->getInfoByUser($_SESSION['user'])['proxies']);
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">管理您的内网穿透隧道</small></h1></div>
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
                            <h3 class="card-title">映射隧道列表</h3>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
						<p><i class="fas fa-info-circle"></i>&nbsp;&nbsp;您已添加 <code><?php echo $use_proxies; ?></code> 条隧道，还可以添加 <code><?php echo $max_proxies == -1 ? "无限制" : ($max_proxies - $use_proxies); ?></code> 条隧道。</p>
                        <table class="table table-striped table-valign-middle">
							<thead>
								<tr>
									<th>ID</th>
									<th nowrap>隧道名称</th>
									<th nowrap>隧道类型</th>
									<th nowrap>绑定域名 / 远程端口</th>
									<th nowrap>服务器节点</th>
									<th nowrap>操作</th>
									<th nowrap>启用隧道</th>
								</tr>
							</thead>
							<tbody>
								<?php
								$ps = Database::query("proxies", Array("username" => $_SESSION['user']));
								$ps = Database::toArray($ps);
								foreach($ps as $pi) {
									$domOrPort = "";
									$domains = json_decode($pi[8], true);
									if($domains && !empty($domains)) {
										for($i = 0;$i < count($domains);$i++) {
											$domOrPort .= $domains[$i];
											if($i < count($domains) - 1) { $domOrPort .= ", "; }
										}
									} elseif(!empty($pi[11])) {
										$domOrPort = $pi[11];
									}
									$nodeName = $nm->getNodeInfo($pi[16])['name'];
									$enable = $pi[14] == "0" ? "checked" : "";
									$enable = $pi[14] == "2" || $pi[14] == "3" ? "disabled" : $enable;
									echo "<tr>
									<td>{$pi[0]}</td>
									<td>{$pi[2]}</td>
									<td>{$pi[3]}</td>
									<td>{$domOrPort}</td>
									<td>{$nodeName}</td>
									<td nowrap><a href='javascript:deleteProxy({$pi[0]});'>删除</a> | <a href='javascript:getProxyInfo({$pi[0]});'>详细信息</a></td>
									<td><div class='custom-control custom-switch'>
										<input type='checkbox' class='custom-control-input' {$enable} id='switchProxy_{$pi[0]}' onclick='toggleProxy({$pi[0]});'>
										<label class='custom-control-label' for='switchProxy_{$pi[0]}'></label>
									</div></td>
								</tr>";
								}
								?>
							</tbody>
						</table>
						<?php
						if(empty($ps)) {
							echo "<div class='text-center' style='margin-top: 16px;'>您还没有创建任何隧道</div>";
						}
						?>
                    </div>
                </div>
			</div>
			<div class="col-lg-4">
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">隧道类型介绍</h3>
                        </div>
                    </div>
                    <div class="card-body fix-text">
						<p><b>提示：</b>XTCP 映射成功率并不高，具体取决于 NAT 设备的复杂度。</p>
						<p><b>TCP 映射</b></p>
						<p>基础的 TCP 映射，适用于大多数服务，例如远程桌面、SSH、Minecraft、泰拉瑞亚等</p>
						<p><b>UDP 映射</b></p>
						<p>基础的 UDP 映射，适用于域名解析、部分基于 UDP 协议的游戏等</p>
						<p><b>HTTP 映射</b></p>
						<p>搭建网站专用映射，并通过 80 端口访问。</p>
						<p><b>HTTPS 映射</b></p>
						<p>带有 SSL 加密的网站映射，通过 443 端口访问，服务器需要支持 SSL。</p>
						<p><b>XTCP 映射</b></p>
						<p>客户端之间点对点 (P2P) 连接协议，流量不经过服务器，适合大流量传输的场景，需要两台设备之间都运行一个客户端。</p>
						<p><b>STCP 映射</b></p>
						<p>安全交换 TCP 连接协议，基于 TCP，访问此服务的用户也需要运行一个客户端，才能建立连接，流量由服务器转发。</p>
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
<div class="modal fade" id="deleteconfirm" style="display: none;" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">删除确认</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
            </div>
            <div class="modal-body">您确定要删除此隧道吗？删除之后将不能恢复！</div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal" onclick="tempdelete = ''">关闭</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="confirmDeleteProxy()">确定</button></div>
        </div>
    </div>
</div>
<script type="text/javascript">
var tempdelete = "";
var csrf_token = "<?php echo $_SESSION['token']; ?>";
function alertMessage(title, body) {
	$("#msg-title").html(title);
	$("#msg-body").html(body);
	$("#modal-default").modal('toggle');
}
function getProxyInfo(id) {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=proxies&getproxyinfo=" + id + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			return;
		},
		success: function() {
			alertMessage("映射信息", htmlobj.responseText);
			return;
		}
	});
}
function toggleProxy(id) {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=proxies&toggle=" + id + "&csrf=" + csrf_token,
		async:true,
		error: function() {
			return;
		},
		success: function() {
			alertMessage("提示信息", htmlobj.responseText);
			return;
		}
	});
}
function deleteProxy(id) {
	// 随便这样吧
	tempdelete = "" + id;
	$("#deleteconfirm").modal('toggle');
}
function confirmDeleteProxy() {
	if(tempdelete != "") {
		var htmlobj = $.ajax({
			type: 'GET',
			url: "?page=panel&module=proxies&delete=" + tempdelete + "&csrf=" + csrf_token,
			async:true,
			error: function() {
				return;
			},
			success: function() {
				tempdelete = "";
				alertMessage("提示信息", htmlobj.responseText);
				return;
			}
		});
	}
}
</script>