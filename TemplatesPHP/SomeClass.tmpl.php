<?php
// Nowdoc Format

$templates['authors'] = <<<'EOD'
<h3>Authors</h3>
<table border="1">
	<thead>
		<tr><th>First Name</th><th>Last Name</th></tr>
	</thead>
	<tbody>
		{{ BEGIN authors }}
			{{ include("authors_row_entry.tpl") }}
		{{ END }}
	</tbody>
</table>
EOD;

$templates['authors_row_entry'] = <<<'EOD'
<tr><th>{{ $fname }}</th><th>{{ $lastname }}</th></tr>
EOD;

?>