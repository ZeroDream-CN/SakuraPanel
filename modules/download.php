<?php
namespace SakuraPanel;

use SakuraPanel;

$page_title = "软件下载";
$rs = Database::querySingleLine("users", Array("username" => $_SESSION['user']));

if(!$rs) {
	exit("<script>location='?page=login';</script>");
}
?>
<style type="text/css">
.fix-text p {
	margin-bottom: 4px;
}
.system-img {
	height: 32px;
}
.download tr td {
	vertical-align: middle;
}
</style>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark"><?php echo $page_title; ?>&nbsp;&nbsp;<small class="text-muted text-xs">下载各种版本的 Frp 客户端</small></h1></div>
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
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex justify-content-between">
                            <h3 class="card-title">客户端下载</h3>
                        </div>
                    </div>
                    <div class="card-body p-0 table-responsive">
                        <table class="download table table-striped table-valign-middle">
							<thead>
								<tr>
									<th style="width: 32px;"></th>
									<th nowrap>系统类型</th>
									<th nowrap>系统架构</th>
									<th nowrap>下载地址</th>
									<th nowrap>下载文件</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><img src="assets/download/windows.png" class="system-img"></td>
									<td nowrap>Windows</td>
									<td nowrap>i386</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_windows_386.zip</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_windows_386.zip" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/windows.png" class="system-img"></td>
									<td nowrap>Windows</td>
									<td nowrap>amd64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_windows_amd64.zip</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_windows_amd64.zip" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>i386</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_386.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_386.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>amd64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_amd64.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_amd64.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>arm</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_arm.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_arm.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>aarch64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_arm64.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_arm64.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>Mips</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>Mips64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips64.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips64.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>Mipsle</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mipsle.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mipsle.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/linux.png" class="system-img"></td>
									<td nowrap>Linux</td>
									<td nowrap>Mips64le</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips64le.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_linux_mips64le.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/freebsd.png" class="system-img"></td>
									<td nowrap>FreeBSD</td>
									<td nowrap>i386</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_freebsd_386.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_freebsd_386.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/freebsd.png" class="system-img"></td>
									<td nowrap>FreeBSD</td>
									<td nowrap>amd64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_freebsd_amd64.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_freebsd_amd64.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
								<tr>
									<td><img src="assets/download/macos.png" class="system-img"></td>
									<td nowrap>MacOS</td>
									<td nowrap>amd64</td>
									<td nowrap><code>https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_darwin_amd64.tar.gz</code></td>
									<td nowrap><a href="https://github.com/fatedier/frp/releases/download/v0.29.0/frp_0.29.0_darwin_amd64.tar.gz" target="_blank"><button class="btn btn-sm btn-success">点击下载</button></a></td>
								</tr>
							</tbody>
						</table>
                    </div>
                </div>
			</div>
		</div>
	</div>
</div>