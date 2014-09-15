<?php

$this->data['header'] = $this->t('{selfregister:selfregister:link_lostpw}');
$this->data['head'] = '<link rel="stylesheet" href="resources/selfregister.css" type="text/css">';

$this->includeAtTemplateBase('includes/header.php'); ?>

<?php if(isset($this->data['error'])){ ?>
	  <div class="error"><?php echo $this->data['error']; ?></div>
<?php }?>

<form method="post" action="lostPassword.php">
<div style="margin: 1em">
	<h1><?php echo $this->t('lpw_head'); ?></h1>

	<p><?php echo $this->t('lpw_para1'); ?></p>

	<table>
		<tr class="even">
		<td>E-mail</td><td>
		<input type="text" size="50" name="emailreg" value="<?php
		if (isset($this->data['email'])) echo htmlspecialchars($this->data['email']);
		?>"/></td></tr>
	</table>

	<p><?php echo $this->t('lpw_para2'); ?></p>

	<p><input type="submit" name="save" value="<?php echo $this->t('submit_mail'); ?>" />

</div>
</form>

<p>
<ul>
	<li><a href="index.php"><?php echo $this->t('return'); ?></a></li>
</ul>
</p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
