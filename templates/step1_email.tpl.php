<?php

$this->data['header'] = $this->t('{selfregister:selfregister:link_newuser}');
$this->data['head'] = '<link rel="stylesheet" href="resources/selfregister.css" type="text/css">';

$this->includeAtTemplateBase('includes/header.php'); ?>

<?php if(isset($this->data['error'])){ ?>
	  <div class="error"><?php echo $this->data['error']; ?></div>
<?php }?>

<form method="post" action="newUser.php">
<?php
	if (isset($this->data['RelayState'])) {
		echo('<input type="hidden" name="RelayState" value="' . $this->data['RelayState'] . '" />');
	}
?>
<div style="margin: 1em">
	<h1><?php echo $this->t('s1_head', $this->data['systemName']); ?></h1>

	<p><?php echo $this->t('s1_para1'); ?></p>

	<table>
		<tr class="even">
		<td>E-mail</td><td>
		<input type="text" size="50" name="emailreg" value="<?php
		if (isset($this->data['email'])) echo htmlspecialchars($this->data['email']);
		?>"/></td></tr>
	</table>

	<p><?php echo $this->t('s1_para2'); ?></p>

	<p><input type="submit" name="save" value="<?php echo $this->t('submit_mail'); ?>" />

</div>
</form>

<h2><?php echo $this->t('new_head_other'); ?></h2>
<ul>
	<li><a href="index.php"><?php echo $this->t('return'); ?></a></li>
<?php if (isset($this->data['logouturl'])) {?>
	<li><a href="<?php echo $this->data['logouturl'];?>"><?php echo $this->t('{status:logout}'); ?></li>
<?php } 
echo '</ul>';

$this->includeAtTemplateBase('includes/footer.php'); ?>
