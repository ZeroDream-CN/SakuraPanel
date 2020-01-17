<?php
namespace SakuraPanel;

use SakuraPanel;

global $_config;

$page_title = "创建隧道";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}

$nm = new SakuraPanel\NodeManager();
$pm = new SakuraPanel\ProxyManager();
$un = $nm->getUserNode($rs['group']);

$proxies_max = $rs['proxies'] == "-1" ? "无限制" : $rs['proxies'];

if(isset($_GET['portrules'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	echo "<p>映射的端口最小为 <code>{$_config['proxies']['min']}</code>，最大为 <code>{$_config['proxies']['max']}</code>。</p>";
	if(!empty($_config['proxies']['protect'])) {
		echo "<p>以下为系统保留的端口范围，不可使用：</p>";
		echo "<ul>";
		foreach($_config['proxies']['protect'] as $key => $value) {
			echo "<li><code>{$key}</code> - <code>{$value}</code></li>";
		}
		echo "</ul>";
		echo "<span>您最多可以使用 {$proxies_max} 个端口</span>";
	}
	exit;
}
if(isset($_GET['randomport'])) {
	ob_clean();
	SakuraPanel\Utils::checkCsrf();
	echo $pm->getRandomPort();
	exit;
}
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.pdesc {
	margin-left: 8px;
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
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">创建一个新的内网穿透隧道</small></h1></div>
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
                            <h3 class="card-title">创建映射隧道</h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
							<div class="col-sm-12">
								<p><b>选择服务器</b> <small class="pdesc">选择您要使用的 Frp 服务器</small></p>
								<p><select class="form-control" id="node">
									<?php
									foreach($un as $server) {
										echo "<option value='{$server[0]}'>{$server[1]} - {$server[2]} ({$server[3]})</option>";
									}
									?>
								</select></p>
							</div>
							<div class="sub-heading">
								<span>基础设置</span>
							</div>
							<div class="col-sm-6">
								<p><b>隧道名称</b><small class="pdesc">3-15 个字符，中英文和数字以及下划线组成</small></p>
								<p><input type="text" class="form-control" id="proxy_name" placeholder="MyProxy" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>隧道类型</b> <small class="pdesc">每种隧道类型的区别请看右侧介绍</small></p>
								<p><select class="form-control" id="proxy_type">
									<option value="tcp">TCP 隧道</option>
									<option value="udp">UDP 隧道</option>
									<option value="http">HTTP 隧道</option>
									<option value="https">HTTPS 隧道</option>
									<option value="stcp">STCP 隧道</option>
									<option value="xtcp">XTCP 隧道</option>
								</select></p>
							</div>
							<div class="col-sm-6">
								<p><b>本地地址</b> <small class="pdesc">要转发到的本机 IP，默认 127.0.0.1 即可</small></p>
								<p><input type="text" class="form-control" id="local_ip" placeholder="127.0.0.1" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>本地端口</b> <small class="pdesc">本地服务的运行端口，例如网站是 80 端口</small></p>
								<p><input type="text" class="form-control" id="local_port" placeholder="80" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>远程端口</b> <small class="pdesc">给访客连接时使用的外部端口 (<a href="javascript:loadPortRules();">查看规则</a>)</small></p>
								<p><input type="text" class="form-control" id="remote_port" placeholder="1234" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>绑定域名</b> <small class="pdesc">仅限 HTTP 和 HTTPS 类型的隧道</small></p>
								<p><input type="text" class="form-control" id="domain" placeholder="example.com" /></p>
							</div>
							<div class="sub-heading">
								<span>高级设置</span>
							</div>
							<div class="col-sm-12">
								<p><b>提示：</b>以下设置均为选填，仅供有需要的用户使用，一般留空即可。</p>
							</div>
							<div class="col-sm-6">
								<p><b>加密传输</b> <small class="pdesc">使用加密来保护传输的数据</small></p>
								<p><select class="form-control" id="use_encryption">
									<option value="true">启用</option>
									<option value="false">关闭</option>
								</select></p>
							</div>
							<div class="col-sm-6">
								<p><b>压缩数据</b> <small class="pdesc">压缩数据来节省宽带和流量使用</small></p>
								<p><select class="form-control" id="use_compression">
									<option value="true">启用</option>
									<option value="false">关闭</option>
								</select></p>
							</div>
							<div class="col-sm-6">
								<p><b>URL 路由</b> <small class="pdesc">指定要转发的 URL 路由，仅限 HTTP 隧道</small></p>
								<p><input type="text" class="form-control" id="locations" placeholder="/" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>Host 重写</b> <small class="pdesc">重写请求头部的 Host 字段，仅限 HTTP 隧道</small></p>
								<p><input type="text" class="form-control" id="host_header_rewrite" placeholder="frp.example.com" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>请求来源</b> <small class="pdesc">给后端区分请求来源用，仅限 HTTP 隧道</small></p>
								<p><input type="text" class="form-control" id="header_X-From-Where" placeholder="frp_node_1" /></p>
							</div>
							<div class="col-sm-6">
								<p><b>访问密码</b> <small class="pdesc">Frpc 以访客模式连接时的密码，仅限 XTCP/STCP</small></p>
								<p><input type="text" class="form-control" id="sk" placeholder="1234567890" /></p>
							</div>
						</div>
                    </div>
					<div class="card-footer">
						<button type="button" class="btn btn-default" onclick="randomPort()">随机端口</button>
						<button type="button" class="btn btn-primary float-right" onclick="addProxy()">完成创建</button>
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
<script type="text/javascript">
var csrf_token = "<?php echo $_SESSION['token']; ?>";
function alertMessage(title, body) {
	$("#msg-title").html(title);
	$("#msg-body").html(body);
	$("#modal-default").modal('toggle');
}
function addProxy() {
	var node                = $("#node").val();
	var proxy_name          = $("#proxy_name").val();
	var proxy_type          = $("#proxy_type").val();
	var local_ip            = $("#local_ip").val();
	var local_port          = $("#local_port").val();
	var remote_port         = $("#remote_port").val();
	var domain              = $("#domain").val();
	var use_encryption      = $("#use_encryption").val();
	var use_compression     = $("#use_compression").val();
	var locations           = $("#locations").val();
	var host_header_rewrite = $("#host_header_rewrite").val();
	var header_X_From_Where = $("#header_X-From-Where").val();
	var sk                  = $("#sk").val();
	var htmlobj = $.ajax({
		type: 'POST',
		url: "?page=panel&module=addproxy&action=addproxy&csrf=" + csrf_token,
		data: {
			node               : node,
			proxy_name         : proxy_name,
			proxy_type         : proxy_type,
			local_ip           : local_ip,
			local_port         : local_port,
			remote_port        : remote_port,
			domain             : domain,
			use_encryption     : use_encryption,
			use_compression    : use_compression,
			locations          : locations,
			host_header_rewrite: host_header_rewrite,
			header_X_From_Where: header_X_From_Where,
			sk                 : sk
		},
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
function loadPortRules() {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=addproxy&portrules&csrf=" + csrf_token,
		async:true,
		error: function() {
			return;
		},
		success: function() {
			alertMessage("端口规则", htmlobj.responseText);
			return;
		}
	});
}
function randomPort() {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=addproxy&randomport&csrf=" + csrf_token,
		async:true,
		error: function() {
			alertMessage("发生错误", htmlobj.responseText);
			return;
		},
		success: function() {
			$("#remote_port").val(htmlobj.responseText);
			return;
		}
	});
}
</script>