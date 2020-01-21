<?php
namespace SakuraPanel;

use SakuraPanel;

$pm = new SakuraPanel\ProxyManager();
$page_title = "用户信息";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}

$um          = new SakuraPanel\UserManager();
$ls          = $um->getLimit($_SESSION['user']);
$inbound     = round($ls['inbound'] / 1024 * 8);
$outbound    = round($ls['outbound'] / 1024 * 8);
$speed_limit = "{$inbound}Mbps 上行 / {$outbound}Mbps 下行";
$signinfo    = Database::querySingleLine("sign", Array("username" => $_SESSION['user']));
$token       = Database::querySingleLine("tokens", Array("username" => $_SESSION['user']))["token"] ?? "Unknown";
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
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">查看您的个人信息</small></h1></div>
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
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">账号信息</h3>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
						<h3 class='text-primary' style='padding: 16px;padding-left: 24px;'><?php echo htmlspecialchars($_SESSION['user']); ?></h3>
						<table class="download table table-striped table-valign-middle" style="width: 100%;font-size: 15px;">
							<tr>
								<td style="width: 30%;"><b>用户 ID</b></td>
								<td><?php echo $rs['id']; ?></td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>注册邮箱</b></td>
								<td><?php echo htmlspecialchars($_SESSION['mail']); ?></td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>注册时间</b></td>
								<td><?php echo date("Y-m-d", $rs['regtime']); ?></td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>用户组别</b></td>
								<td><?php echo htmlspecialchars($rs['group']); ?></td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>访问密钥</b></td>
								<td><?php echo htmlspecialchars($token); ?></td>
							</tr>
						</table>
					</div>
				</div>
				<div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">修改密码</h3>
                        </div>
                    </div>
					<form method="post" action="?page=panel&module=profile&action=updatepass&csrf=<?php echo $_SESSION['token']; ?>">
						<div class="card-body">
							<p><b>请输入旧密码</b></p>
							<p><input type="password" class="form-control" name="oldpass"></p>
							<p><b>请输入新密码</b></p>
							<p><input type="password" class="form-control" name="newpass"></p>
							<p><b>请再输入一次</b></p>
							<p><input type="password" class="form-control" name="newpass1"></p>
						</div>
						<div class="card-footer">
							<button type="submit" class="btn btn-primary float-right">确认修改</button>
						</div>
					</form>
				</div>
			</div>
			<div class="col-lg-6">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">映射信息</h3>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
						<p style='padding: 16px;padding-left: 24px;padding-bottom: 8px;'>关于您的内网穿透使用情况</p>
						<table class="download table table-striped table-valign-middle" style="width: 100%;font-size: 15px;">
							<tr>
								<td style="width: 30%;"><b>剩余流量</b></td>
								<td><?php echo htmlspecialchars(round($rs['traffic'] / 1024, 2)); ?> GB</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>今日已用</b></td>
								<td><?php echo htmlspecialchars(round($um->getTodayTraffic($_SESSION['user']) / 1024 / 1024 / 1024, 2)); ?> GB</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>隧道数量</b></td>
								<td><?php echo htmlspecialchars($rs['proxies']); ?> 条</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>宽带速度</b></td>
								<td><?php echo htmlspecialchars($speed_limit); ?></td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>创建隧道</b></td>
								<td><?php echo htmlspecialchars($pm->getUserProxies($_SESSION['user'])); ?> 条</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>总计签到</b></td>
								<td><?php echo htmlspecialchars($signinfo['totalsign']); ?> 天</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>获得流量</b></td>
								<td><?php echo htmlspecialchars($signinfo['totaltraffic']); ?> GB</td>
							</tr>
							<tr>
								<td style="width: 30%;"><b>上次签到</b></td>
								<td><?php echo date("Y-m-d H:i:s", $signinfo['signdate']); ?></td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
