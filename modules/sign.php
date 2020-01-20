<?php
namespace SakuraPanel;

use SakuraPanel;

include(ROOT . "/core/Parsedown.php");

global $_config;

$markdown = new Parsedown();
$markdown->setSafeMode(true);
$markdown->setBreaksEnabled(true);
$markdown->setUrlsLinked(true);
$page_title = "每日签到";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));
$pm = new SakuraPanel\ProxyManager();
$nm = new SakuraPanel\NodeManager();
$um = new SakuraPanel\UserManager();

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}

$user_traffic = $rs['traffic'] - ($um->getTodayTraffic($_SESSION['user']) / 1024 / 1024);

if(isset($_GET['sign'])) {
	
	ob_clean();
	
	SakuraPanel\Utils::checkCsrf();
	
	// 欢迎来到喜闻乐见的欧皇与非酋抽流量
	
	if(!$_config['sign']['enable']) {
		exit("本站暂未开启签到功能~");
	}
	
	// 欧皇判定范围
	$good_rand = round($_config['sign']['max'] * 0.7);
	// 非酋判定范围
	$bad_rand = round($_config['sign']['max'] * 0.2);
	// 随机流量
	$rand = mt_rand($_config['sign']['min'], $_config['sign']['max']);
	
	$rs = Database::querySingleLine("sign", Array("username" => $_SESSION['user']));
	if($rs) {
		if(isset($rs['signdate'])) {
			if(Intval(date("Ymd")) >= Intval(date("Ymd", $rs['signdate'])) + 1) {
				$totaltraffic = $rs['totaltraffic'] == "" ? "0" : $rs['totaltraffic'];
				$totalsign    = $rs['totalsign']    == "" ? "0" : $rs['totalsign'];
				Database::update("sign", Array("signdate" => time(), "totaltraffic" => $totaltraffic + $rand, "totalsign" => $totalsign + 1), Array("username" => $_SESSION['user']));
				Database::update("users", Array("traffic" => $user_traffic + ($rand * 1024)), Array("username" => $_SESSION['user']));
				Database::update("proxies", Array("status" => "0"), Array("username" => $_SESSION['user'], "status" => "2"));
				$randtext = "今天运气不错，";
				if($rand >= $good_rand) {
					$randtext = "今天欧皇手气，共";
				} elseif($rand <= $bad_rand) {
					$randtext = "今天是非酋，只";
				}
				exit("签到成功，{$randtext}获得了 {$rand}GB 流量，目前您的剩余流量为 " . round(($user_traffic + ($rand * 1024)) / 1024, 2) . "GB。");
			} else {
				exit("您今天已经签到过了，请明天再来");
			}
		} else {
			Database::insert("sign", Array("id" => null, "username" => $_SESSION['user'], "signdate" => time(), "totaltraffic" => $rand, "totalsign" => 1));
			Database::update("users", Array("traffic" => $user_traffic + ($rand * 1024)), Array("username" => $_SESSION['user']));
			Database::update("proxies", Array("status" => "0"), Array("username" => $_SESSION['user'], "status" => "2"));
			exit("签到成功，这是你第一次签到，获得了 {$rand}GB 流量。");
		}
	} else {
		Database::insert("sign", Array("id" => null, "username" => $_SESSION['user'], "signdate" => time(), "totaltraffic" => $rand, "totalsign" => 1));
		Database::update("users", Array("traffic" => $user_traffic + ($rand * 1024)), Array("username" => $_SESSION['user']));
		Database::update("proxies", Array("status" => "0"), Array("username" => $_SESSION['user'], "status" => "2"));
		exit("签到成功，这是你第一次签到，获得了 {$rand}GB 流量。");
	}
}

$signed = false;
$ss = Database::querySingleLine("sign", Array("username" => $_SESSION['user']));
if($ss) {
	if(isset($ss['signdate']) && Intval(date("Ymd")) < Intval(date("Ymd", $ss['signdate'])) + 1) {
		$signed = true;
	}
}
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.info-icon {
	margin-bottom: 16px;
}
.sub-heading {
	width: 100%;
    height: 0!important;
    border-top: 1px solid #e9f1f1!important;
    text-align: center!important;
    margin-top: 32px!important;
    margin-bottom: 40px!important;
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
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">签到以获取免费的流量</small></h1></div>
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
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">每日签到</h3>
                        </div>
                    </div>
                    <div class="card-body table-responsive">
						<div class="row">
							<div class="col-sm-9">
								<?php
								if($signed) {
								?>
								<h3 class="text-success">您今天已经签到过了噢</h3>
								<p>继续保持签到就可以获得更多的流量</p>
								<?php
								} else {
								?>
								<h3 class="text-warning">您今天还没有签到哦</h3>
								<p>立即签到就可以获得免费的流量，可用于内网穿透使用</p>
								<?php
								}
								?>
							</div>
							<div class="col-sm-3 text-center" style="padding-top: 16px;">
								<button class="btn btn-primary" onclick="sign()" <?php echo $signed ? "disabled" : ""; ?>>立即签到</button>
							</div>
						</div>
						<div class="sub-heading">
							<span>统计信息</span>
						</div>
						<div class="row">
							<div class="col-sm-4 text-center">
								<h1 class='info-icon'><i class='fas fa-calendar-check'></i></h1>
								<p>总计已签到 <?php echo $ss['totalsign'] == "" ? "0" : $ss['totalsign'];?> 天</p>
							</div>
							<div class="col-sm-4 text-center">
								<h1 class='info-icon'><i class='fas fa-rocket'></i></h1>
								<p>共获得流量 <?php echo $ss['totaltraffic'] == "" ? "0" : $ss['totaltraffic'];?> GB</p>
							</div>
							<div class="col-sm-4 text-center">
								<h1 class='info-icon'><i class='fas fa-clock'></i></h1>
								<p>上次签到于 <?php echo $ss['signdate'] == "" ? "从未签到" : date("Y-m-d", $ss['signdate']);?></p>
							</div>
						</div>
                    </div>
                </div>
            </div>
			<div class="col-lg-5">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">签到说明</h3>
                        </div>
                    </div>
                    <div class="card-body fix-text">
						<p>欢迎使用签到系统，通过每天登录签到您可以获得免费的流量，可以用于抵消使用内网穿透产生的流量费用。</p>
						<div class="sub-heading">
							<span>签到配置</span>
						</div>
						<p>当前站点的签到系统启用状态：<?php echo $_config['sign']['enable'] ? "<span class='text-success'>已启用</span>" : "<span class='text-danger'>已禁用</span>"; ?>。</p>
						<p>通过签到可以随机获得 <?php echo $_config['sign']['min']; ?> ~ <?php echo $_config['sign']['max']; ?> GB 流量。</p>
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
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="window.location.reload()">确定</button></div>
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
function sign() {
	var htmlobj = $.ajax({
		type: 'GET',
		url: "?page=panel&module=sign&sign&csrf=" + csrf_token,
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
</script>
