<?php
$data = $vars;
?>
<?php $this->render("header", $data["header"]); ?>
<?php if (!empty($data["books"])) { ?>
<?php } elseif (!empty($data["books"])) { ?>
<?php } else { ?>
<?php } ?>
<?php if (count($data["books"]) > 0) { ?>
<?php } elseif (count($data["books"]) > 1) { ?>
<?php } else { ?>
<?php } ?>
<div>book count: <?php echo count($data["books"]); ?></div>
<div>
	<table>
<?php foreach ($data["books"] as $i => $data) { ?>
		<tr>
			<td><?php echo $data["id"]; ?></td>
			<td><?php echo $data["name"]; ?></td>
			<td><?php echo $data["price"]; ?></td>
			<td><?php echo $data["cover"]; ?></td>
		</tr>
<?php $data = $vars; } ?>
	</table>
</div>
<div>
	<table>
<?php foreach ($data["books"] as $i => $row) { ?>
<?php $data = $row; ?>
		<tr>
			<td><?php echo $data["id"]; ?></td>
			<td><?php echo $data["name"]; ?></td>
			<td><?php echo $data["price"]; ?></td>
			<td><?php echo $data["cover"]; ?></td>
		</tr>
<?php $data = $vars; } ?>
	</table>
</div>
<?php $this->render("footer", $data["footer"]); ?>