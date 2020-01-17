<?php
namespace SakuraPanel;

use SakuraPanel;

global $_config;
$module = $_GET['module'] ?? "";

$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));
?>
<!DOCTYPE html>
<html lang="zh-CN">
	
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<title>管理面板 :: <?php echo $_config[ 'sitename']; ?> - <?php echo $_config[ 'description']; ?></title>
		<!-- Font Awesome Icons -->
		<link rel="stylesheet" href="assets/panel/plugins/fontawesome-free/css/all.min.css">
		<!-- IonIcons -->
		<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
		<!-- Theme style -->
		<link rel="stylesheet" href="assets/panel/dist/css/adminlte.min.css">
		<!-- Google Font: Source Sans Pro -->
		<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet"></head>
	
	<body class="hold-transition sidebar-mini">
		<div class="wrapper">
			<!-- Navbar -->
			<nav class="main-header navbar navbar-expand navbar-white navbar-light">
				<!-- Left navbar links -->
				<ul class="navbar-nav">
					<li class="nav-item">
						<a class="nav-link" data-widget="pushmenu" href="#">
							<i class="fas fa-bars"></i>
						</a>
					</li>
					<li class="nav-item d-none d-sm-inline-block">
						<a href="?page=panel&module=home" class="nav-link">主页</a></li>
				</ul>
				<!-- Right navbar links -->
				<ul class="navbar-nav ml-auto">
					<li class="nav-item">
						<a class="nav-link" href="?page=logout&csrf=<?php echo $_SESSION['token']; ?>" title="退出登录">
							登出&nbsp;&nbsp;
							<i class="fas fa-sign-out-alt"></i>
						</a>
					</li>
				</ul>
			</nav>
			<!-- /.navbar -->
			<!-- Main Sidebar Container -->
			<aside class="main-sidebar sidebar-dark-primary elevation-4">
				<!-- Brand Logo -->
				<a href="?page=panel&module=home" class="brand-link">
					<center>
						<span class="brand-text font-weight-light">
							<?php echo $_config[ 'sitename']; ?></span>
					</center>
				</a>
				<!-- Sidebar -->
				<div class="sidebar">
					<!-- Sidebar user panel (optional) -->
					<div class="user-panel mt-3 pb-3 mb-3 d-flex">
						<div class="image">
							<img src="https://secure.gravatar.com/avatar/<?php echo md5($_SESSION['mail']); ?>?s=64" class="img-circle elevation-2" alt="User Image"></div>
						<div class="info">
							<a href="#" class="d-block">
								<?php echo htmlspecialchars($_SESSION[ 'user']); ?></a>
						</div>
					</div>
					<!-- Sidebar Menu -->
					<nav class="mt-2">
						<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
							<!-- <li class="nav-header">EXAMPLES</li> -->
							<li class="nav-item">
								<a href="?page=panel&module=home" class="nav-link <?php echo $module == "home" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-tachometer-alt"></i>
									<p>管理面板</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=profile" class="nav-link <?php echo $module == "profile" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-user"></i>
									<p>用户信息</p>
								</a>
							</li>
							<li class="nav-header">内网穿透</li>
							<li class="nav-item">
								<a href="?page=panel&module=proxies" class="nav-link <?php echo $module == "proxies" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-list"></i>
									<p>隧道列表</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=addproxy" class="nav-link <?php echo $module == "addproxy" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-plus"></i>
									<p>创建隧道</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=sign" class="nav-link <?php echo $module == "sign" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-check-square"></i>
									<p>每日签到</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=download" class="nav-link <?php echo $module == "download" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-download"></i>
									<p>软件下载</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=configuration" class="nav-link <?php echo $module == "configuration" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-file"></i>
									<p>配置文件</p>
								</a>
							</li>
							<?php
							if($rs['group'] == "admin") {
								?>
							<li class="nav-header">管理员</li>
							<li class="nav-item">
								<a href="?page=panel&module=userlist" class="nav-link <?php echo $module == "userlist" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-users"></i>
									<p>用户管理</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=nodes" class="nav-link <?php echo $module == "nodes" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-server"></i>
									<p>节点管理</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=traffic" class="nav-link <?php echo $module == "traffic" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-paper-plane"></i>
									<p>流量统计</p>
								</a>
							</li>
							<li class="nav-item">
								<a href="?page=panel&module=settings" class="nav-link <?php echo $module == "settings" ? "active" : ""; ?>">
									<i class="nav-icon fas fa-wrench"></i>
									<p>站点设置</p>
								</a>
							</li>
								<?php
							}
							?>
						</ul>
					</nav>
					<!-- /.sidebar-menu --></div>
				<!-- /.sidebar --></aside>
			<!-- Content Wrapper. Contains page content -->
			<div class="content-wrapper">
				<?php
				$page = new SakuraPanel\Pages();
				if(isset($_GET['module']) && preg_match("/^[A-Za-z0-9\_\-]{1,16}$/", $_GET['module'])) {
					$page->loadModule($_GET['module']);
				} else {
					$page->loadModule("home");
				}
				?>
			</div>
			<!-- /.content-wrapper -->
			<!-- Control Sidebar -->
			<aside class="control-sidebar control-sidebar-dark">
				<!-- Control sidebar content goes here --></aside>
			<!-- /.control-sidebar -->
			<!-- Main Footer -->
			<footer class="main-footer">
				<strong>Copyright &copy; <?php echo date( "Y"); ?> <a href="http://<?php echo $_SERVER['SERVER_NAME']; ?>"><?php echo $_config[ 'sitename']; ?></a>.</strong>
				All rights reserved.
				<div class="float-right d-none d-sm-inline-block">Powered by <b>Sakura Panel</b>
				</div>
			</footer>
		</div>
		<!-- ./wrapper -->
		<!-- REQUIRED SCRIPTS -->
		<!-- jQuery -->
		<script src="assets/panel/plugins/jquery/jquery.min.js"></script>
		<!-- Bootstrap -->
		<script src="assets/panel/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
		<!-- AdminLTE -->
		<script src="assets/panel/dist/js/adminlte.js"></script>
	</body>

</html>