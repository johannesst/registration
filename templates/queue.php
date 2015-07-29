<?php
\OCP\Util::addStyle('settings','settings');
?>
<form action="<?php print_unescaped(OC_Helper::linkToRoute('registration.register.changeQueue' )) ?>" method="post">
	
<table  class="hascontrols grid">
	<thead>
		<tr>
			<th id="headerName" scope="col"><?php p($l->t('Username'))?></th>
			<th class="mailAddress" scope="col"><?php p($l->t( 'Email' )); ?></th>
			<th class="mailAddress" scope="col"><?php p($l->t( 'State' )); ?></th>
			<th class=="headerGroups" scope="col"></th>
		</tr>
	</thead>
	<tbody>
<?php	
foreach ( $_['accounts'] as $account ) {
	//Benutzername,Password, status (kann activated,banned oder registered sein, kann nur eins davon sein
	echo '<tr><td class="displayName"><span> ' .$account['username'] .'</td></span>';
	echo '<td class="mailAddress"><span>'.$account['email'].'</span></td>';
	echo '<td class="groups"><span> ';
	echo $account['state'];
	echo '</td></span>';
	echo '<td><span>';
		echo '<button  type="submit" name="enable" id="enable" value="'.$account['email'];
		if ( $account['state'] === 'banned') {
			echo 'disabled="disabled" style="background: gainsboro;color: whitesmoke;"';
		}
		echo '">';
		print_unescaped($l->t('Enable account'));
		echo '</button>';
		echo '<button min="30" type="submit" name="ban" id="ban" value="'.$account['email'];
		if ( $account['state'] === 'banned') {
			echo 'disabled="disabled" style="background: gainsboro;color: whitesmoke;"';
 		}
		echo '">';
		print_unescaped($l->t('Ban account'));
		echo '</button>';
	echo '</td></span>';
	echo '</tr>';
	}

?>
</ul>

</form>
