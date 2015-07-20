<?php
script('registration', 'settings');
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
	<label for="allowed_domains"><?php p($l->t('Allowed domains for registration')); ?></br>
	<?php p($l->t('Enter a semicolon-seperated list of allowed domains. Example: owncloud.com;github.com'));?> 
	</label>
	<p> 
	<input type="text" id="allowed_domains" name="allowed_domains" value=<?php p($_['allowed']);?>>
	</p>
	<p> 
		<label for="needs_activation"><?php p($l->t('Registration needs Activation by Administrator')); ?></label>
		<input type="checkbox" id="needs_activation" name="needs_activation" value=<?php p($_['needs_activation']); ?>>
	</p>


</form>
