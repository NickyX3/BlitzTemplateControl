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