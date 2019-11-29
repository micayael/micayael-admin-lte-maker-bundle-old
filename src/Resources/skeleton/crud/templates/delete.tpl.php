{% extends 'admin.html.twig' %}

{% block page_title %}

    {{ 'crud.title.delete'|trans({'%entity_class_name%': '<?= $entity_class_name ?>'}, 'MicayaelAdminLteMakerBundle') }}

{% endblock %}

{% block page_content %}

    <div class="row">
        <div class="col-md-12">

            {% embed '@MicayaelAdminLteMaker/Widgets/context_menu.html.twig' %}

                {% block actions %}
                    <li>
                        {{ create_link('show', '<?= $route_name ?>_show', {'<?= $entity_identifier ?>': <?= $entity_twig_var_singular ?>.<?= $entity_identifier ?>}, 'ROLE_<?= $entity_class_name_upper ?>_READ') }}
                    </li>
                    <li>
                        {{ create_link('edit', '<?= $route_name ?>_edit', {'<?= $entity_identifier ?>': <?= $entity_twig_var_singular ?>.<?= $entity_identifier ?>}, 'ROLE_<?= $entity_class_name_upper ?>_UPDATE') }}
                    </li>
                    <li>
                        {{ create_link('index', '<?= $route_name ?>_index', {}, 'ROLE_<?= $entity_class_name_upper ?>_READ') }}
                    </li>
                {% endblock %}

            {% endembed %}

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            {% embed '@AdminLTE/Widgets/box-widget.html.twig' with { 'boxtype': 'danger' } %}

                {% block box_title %}
                    {{ 'crud.delete.are_you_sure'|trans({}, 'MicayaelAdminLteMakerBundle') }}
                {% endblock %}

                {% block box_body %}

                    <table class="table">
                        <tbody>
<?php foreach ($entity_fields as $field): ?>
<?php if($field['fieldName'] === 'id') continue; ?>
                            <tr>
                                <th class="col-lg-2"><?= ucfirst($field['fieldName']) ?>:</th>
                                <td>{{ <?= $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field) ?> }}</td>
                            </tr>
<?php endforeach; ?>
                        </tbody>
                    </table>

                {% endblock %}

                {% block box_footer %}

                    <span class="pull-right">
                        {{ include('<?= $route_name ?>/_delete_form.html.twig') }}
                    </span>

                {% endblock %}

            {% endembed %}

        </div>
    </div>

{% endblock %}