<button type="button" class="navbar-toggle menu-toggler pull-left" id="menu-toggler" data-target="#sidebar">
	<span class="sr-only">Toggle sidebar</span>
	<span class="icon-bar"></span>
	<span class="icon-bar"></span>
	<span class="icon-bar"></span>
</button>

<div class="navbar-header pull-left">
	<table>
		<tr>
			<td width="20%">
				<a href="index.php" class="navbar-brand">
					<small>
						<i class="fa fa-leaf"></i>
						<?php include "../lib/subtitel.php"; ?>
					</small>
				</a>
			</td>
			<td></td>
		</tr>
	</table>
</div>

<div class="navbar-buttons navbar-header pull-right" role="navigation">
	<ul class="nav ace-nav">
		<li class="light-blue dropdown-modal">
			<a data-toggle="dropdown" href="#" class="dropdown-toggle">
				<img class="nav-user-photo" src="../<?php echo $foto_user; ?>" alt="User Profil" />
				<span class="user-info">
					<small>Welcome,</small>
					<?php echo $_nama; ?>
				</span>
				<i class="ace-icon fa fa-caret-down"></i>
			</a>
			<ul class="user-menu dropdown-menu-right dropdown-menu dropdown-yellow dropdown-caret dropdown-close">
				<li>
					<a href="change_pwd.php">
						<i class="ace-icon fa fa-cog"></i>
						Change Password
					</a>
				</li>
				<li>
					<a href="profile.php">
						<i class="ace-icon fa fa-user"></i>
						Profile
					</a>
				</li>
				<li class="divider"></li>
				<li>
					<a href="logout.php">
						<i class="ace-icon fa fa-power-off"></i>
						Logout
					</a>
				</li>
			</ul>
		</li>
	</ul>
</div>
<div class="navbar-header pull-right">
	<a href="#" class="navbar-brand"><small></small></a>
</div>
