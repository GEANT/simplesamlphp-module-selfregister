<?php

$this->data['header'] = $this->t('{selfregister:selfregister:link_deluser}');
$this->data['head'] = '<link rel="stylesheet" href="resources/selfregister.css" type="text/css">';

$this->includeAtTemplateBase('includes/header.php'); ?>

<?php if(isset($this->data['error'])){ ?>
	  <div class="error"><?php echo $this->data['error']; ?></div>
<?php }?>
<?php if(isset($this->data['userMessage'])){ ?>
	<div class="umesg"><?php echo $this->t($this->data['userMessage']); ?></div>
<?php }?>

<h1><?php echo $this->t('review_head'); ?></h1>
<p>
	<?php echo $this->t('review_intro', array('%UID%' => '<b>' . $this->data['uid'] . '</b>') ); ?>
</p>


<?php print $this->data['formHtml']; ?>

<h2><?php echo $this->t('new_head_other'); ?></h2>
<ul>
	<li><a href="index.php"><?php echo $this->t('return'); ?></a></li>
	<li><a href="reviewUser.php?logout=true"><?php echo $this->t('{status:logout}'); ?></a></li>
</ul>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
