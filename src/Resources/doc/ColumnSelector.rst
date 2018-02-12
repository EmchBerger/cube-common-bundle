Using ColumnSelector
====================

The user can show and hide column values. Showing and hiding the columns is done by adapting css rules.
The users selection is saved (default in UserSettings table) and loaded at next page visit.
Multiple pages and multiple tables per page are supported.

Mark columns to allow hiding:
-----------------------------

Variants to mark columns to allow to hide, do the following on the topmost cell:

+-----------------------------+----------------------------------------------+----------------------------------------+
| add html attribute          | description                                  | value for afterColumnSelectionTables() |
+=============================+==============================================+========================================+
| id="colXxxx"                | set id beginning with col + uppercase letter | id_colXx (is default)                  |
+-----------------------------+----------------------------------------------+----------------------------------------+
| id="xxxCol"                 | set id ending with Col                       | id_xCol                                |
+-----------------------------+----------------------------------------------+----------------------------------------+
| class="... xxxCol"          | set last class ending on Col                 | class_xCol                             |
+-----------------------------+----------------------------------------------+----------------------------------------+

to prevent hiding when above matches:

+---------------------------+---------------------------+----------+
| class="noHideCol"         | set class "noHideCol"     | (always) |
+---------------------------+---------------------------+----------+

Mark columns when colspan is used
---------------------------------

Set the same class on all cells of a column. The class name must start with "col" or end with "Col".

This is only necessary when there are cells with attribute colspan="n".

Initialize ColumnSelector
-------------------------

it is simplest to define a macro for column selection

  .. code-block:: twig

    {% macro columnSelectorBtn(optionalButtonId) %}
        {% import 'CubeToolsCubeCommonBundle:ColumnSelector:columnSelector.macro.twig' as colSel %}
        <button href="javascript:" class="colsSelector" > {# or <a>, <span> or ... #}
            ...
        </button>
        {{ colSelMc.nearButton(optionalButtonId) }}
    {% endmacro %}

This macro is included somewhere in the table to allow hiding the columns for.

Somewhere after all tables, you initialise all the setting with a macro call:

  .. code-block:: twig

    {{ colSel.afterColumnSelectionTables(optionalColumnType|default('id_colXx')) }} {# see value from above #}

It is handy if you do this in a macro as well (to spare macro imports).

Initialise ColumnSelector with bootstrap popup
----------------------------------------------

When the column selection button shall open a bootstrap popover dialogue, then do an adapted initialisation.
It does

Define it as a macro

  .. code-block:: twig

    {% macro afterColumnSelectionTables(optionalColumnType) %}
        {% import 'CubeToolsCubeCommonBundle:ColumnSelector:columnSelector.macro.twig' as colSel %}
        {{ colSel.afterColumnSelectionTables(optionalColumnType|default('id_colXx')) }}
        <script> /*colSel filterHelper*/
            $(document).ready(function () {
                cubetools.colsSelector.initializeBootstrapPopover(
                    '{{ 'your custom title'|trans|escape('js') }}',
                    '.colSelCloseBtn', {# optional close button class, this is the default #}
                    'form.colSelForm .columnSelection input', {# optional input selector, this is the default #}
                    document.body {# optional root where the dialogue is found, this is the default #}
                );
            });
        </script>
        <div id="popoverContentTemplate" class="hidden"> {# this id must be set, element will be used as popup dialogue #}
            <form class="colSelForm" action="javascript:void('columns')" style="margin-bottom: 0.4em;">
                <input type="hidden" name="id"/>
                <fieldset>
                    <span class="columnSelection checkbox" style="display: none;"> {# class columnSelection is mandatory#}
                        <label for="__inpId__" class="checkbox"> {# __inpId__ will be replaced #}
                            <input id="__inpId__" type="checkbox" name="__colId__"/> {# and __colId__ also #}
                            <span>__colLabel__</span> {# and __colLabel__ is replaced by the column headings text #}
                        </label>
                    </span>
                </fieldset>
            </form>
            <div class="text-right"><button class="btn btn-small colSelCloseBtn">Close</button></div> {# optional close btn #}
        </div>
    {% endmacro %}
