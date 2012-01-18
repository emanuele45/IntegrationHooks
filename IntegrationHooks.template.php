<?php

function template_integrationHooks_above()
{
}
function template_integrationHooks_below()
{
	global $context;

	if (empty($context['hooks_filters']))
		return;

	echo '
	<script type="text/javascript"><!-- // --><![CDATA[
		var tblHeader = document.getElementById(\'' . $context['default_list'] . '\').getElementsByTagName(\'th\')[0];
		tblHeader.innerHTML += ' . JavaScriptEscape($context['hooks_filters']) . ';

		function integrationHooks_switchstatus(id)
		{
			var elem = document.getElementById(\'input_\'+id);
			if (elem.value == \'enable\')
				elem.value = \'disable\';
			else if (elem.value == \'disable\')
				elem.value = \'enable\';

			deselectAll(elem.form);

			document.forms["' . $context['default_list'] . '"].submit();
		}

		function deselectAll(oForm, sMask, bIgnoreDisabled)
		{
			for (var i = 0; i < oForm.length; i++)
			{
				if (!(\'name\' in oForm[i]) || (typeof(sMask) == \'string\' && oForm[i].name.substr(0, sMask.length) != sMask && oForm[i].id.substr(0, sMask.length) != sMask))
					continue;

				if (!oForm[i].disabled || (typeof(bIgnoreDisabled) == \'boolean\' && bIgnoreDisabled))
					oForm[i].checked = false;
			}
		}
	// ]]></script>';
}

?>