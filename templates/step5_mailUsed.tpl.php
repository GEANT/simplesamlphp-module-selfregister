<?php 

$this->data['header'] = $this->t('{selfregister:selfregister:link_newuser}');
$this->data['head'] = '<link rel="stylesheet" href="resources/selfregister.css" type="text/css">';

$this->includeAtTemplateBase('includes/header.php'); ?>

<div style="margin: 1em">
	  <h1><?php echo $this->t('new_mailUsed_head'); ?></h1>
	  <p><?php echo $this->t('new_mailUsed_para1', $this->data['systemName']); ?></p>
	  <ul>
	    <li><a href="newUser.php"><?php echo $this->t('link_newuser'); ?></li>
	    <li><a href="index.php"><?php echo $this->t('return'); ?></a></li>
	  </ul>
</div>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
