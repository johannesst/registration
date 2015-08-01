<?php
script('registration', 'settings');
\OCP\Util::addStyle('registration', 'style');
?>
<form id="registration" class="section">
	<h2><?php p($l->t('Registration')); ?></h2>
	<p>
	<label for="registered_user_group"><?php p($l->t('Default group that all registered users belong')); ?></label>
	<select id="registered_user_group" name="registered_user_group">
		<option value="none" <?php echo $_['current'] === 'none' ? 'selected="selected"' : ''; ?>><?php p($l->t('None')); ?></option>
<?php
foreach ( $_['groups'] as $group ) {
	$selected = $_['current'] === $group ? 'selected="selected"' : '';
	echo '<option value="'.$group.'" '.$selected.'>'.$group.'</option>';
}
?>
	</select>
	</p>
	<p>
	<label for="allowed_domains"><?php p($l->t('Allowed domains for registration')); ?>
	</label>
	<input class="input-margin " type="text" id="allowed_domains" name="allowed_domains" value=<?php p($_['allowed']);?>>
	</p>
<ul class="indent "><li>
	 <?php p($l->t('Enter a semicolon-seperated list of allowed domains.'))?>
</li><li>
	<?php p($l->t('Example: owncloud.com;github.com'));?> 
</li>
</ul>
	<p> 
		<label for="needs_activation"><?php p($l->t('Registered accounts needs activation by administrator or moderator')); ?></label>
		<input type="checkbox" id="needs_activation" name="needs_activation" value="checked" <?php p($_['needs_activation']); ?>>
	</p>

	<p>
	<label for="registrators_group"><?php p($l->t('Group of users which are allowed to approve pending registrations')); ?></label>
	<select id="registrators_group" name="registrators_group">
		<option value="none" <?php echo $_['currentregistratorsgroup'] === 'none' ? 'selected="selected"' : ''; ?>><?php p($l->t('None')); ?></option>
<?php
foreach ( $_['groups'] as $group ) {
	$selected = $_['currentregistratorsgroup'] === $group ? 'selected="selected"' : '';
	echo '<option value="'.$group.'" '.$selected.'>'.$group.'</option>';
}
?>
	</select>
	</p>

</form>
