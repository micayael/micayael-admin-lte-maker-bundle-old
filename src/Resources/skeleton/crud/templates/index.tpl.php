{% extends 'admin.html.twig' %}

{% block page_title %}

    {{ 'crud.title.index'|trans({'%entity_class_name_plural%': '<?= $entity_class_name_plural; ?>'}, 'MicayaelAdminLteMakerBundle') }}

{% endblock %}

{% block breadcrumb %}

    {% embed '@MicayaelAdminLteMaker/Widgets/breadcrumb.html.twig' %}

        {% block content %}
        <li class="active"><?= $entity_class_name_plural; ?></li>
        {% endblock %}

    {% endembed %}

{% endblock %}

{% block page_content %}

    <div class="row">
        <div class="col-md-12">

            {% embed '@MicayaelAdminLteMaker/Widgets/context_menu.html.twig' %}

                {% block actions %}
                    <li>
                        {{ create_link('new', '<?= $route_name; ?>_new', {}, 'ROLE_<?= $entity_class_name_upper; ?>_CREATE') }}
                    </li>
                {% endblock %}

            {% endembed %}

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">

            {% embed '@AdminLTE/Widgets/box-widget.html.twig' %}

                {% block box_body %}

                    <div class="table-responsive">

                        <table class="table">
                            <thead>
                                <tr>
<?php foreach ($entity_fields as $field): ?>
<?php if ('id' === $field['fieldName']) {
    continue;
} ?>
                                    <th>{{ knp_pagination_sortable(pagination, '<?= ucfirst($field['fieldName']); ?>', '<?= substr($entity_twig_var_singular, 0, 1); ?>.<?= $field['fieldName']; ?>') }}</th>
<?php endforeach; ?>
                                    <th>{{ 'crud.list.actions'|trans({}, 'MicayaelAdminLteMakerBundle') }}</th>
                                </tr>
                            </thead>
                            <tfoot>
                            <tr>
                                <td>
    
                                    {% include '@MicayaelAdminLteMaker/Partials/paginator.html.twig' %}

                                </td>
                            </tr>
                            </tfoot>
                            <tbody>

                            {% for <?= $entity_twig_var_singular; ?> in pagination %}
                                <tr>
<?php foreach ($entity_fields as $field): ?>
<?php if ('id' === $field['fieldName']) {
    continue;
} ?>
<?php if ('boolean' === $field['type']): ?>
                                    <td class="text-center">{{ boolean_value(<?= $entity_twig_var_singular; ?>.<?= $field['fieldName']; ?>) }}</td>
<?php elseif (in_array($field['type'], ['datetime_immutable', 'datetime'])): ?>
                                    <td class="text-center">{{ <?= $entity_twig_var_singular; ?>.<?= $field['fieldName']; ?>|date('d-m-Y H:i:s') }}</td>
<?php elseif (in_array($field['type'], ['date_immutable', 'date'])): ?>
                                    <td class="text-center">{{ <?= $entity_twig_var_singular; ?>.<?= $field['fieldName']; ?>|date('d-m-Y') }}</td>
<?php elseif (in_array($field['type'], ['time_immutable', 'time'])): ?>
                                    <td class="text-center">{{ <?= $entity_twig_var_singular; ?>.<?= $field['fieldName']; ?>|date('H:i:s') }}</td>
<?php else: ?>
                                    <td>{{ <?= $helper->getEntityFieldPrintCode($entity_twig_var_singular, $field); ?> }}</td>
<?php endif; ?>
<?php endforeach; ?>
                                    <td>
                                        {{ create_button('show', '<?= $route_name; ?>_show', {'<?= $entity_identifier; ?>': <?= $entity_twig_var_singular; ?>.<?= $entity_identifier; ?>}, 'ROLE_<?= $entity_class_name_upper; ?>_READ') }}
                                        {{ create_button('edit', '<?= $route_name; ?>_edit', {'<?= $entity_identifier; ?>': <?= $entity_twig_var_singular; ?>.<?= $entity_identifier; ?>}, 'ROLE_<?= $entity_class_name_upper; ?>_UPDATE') }}
                                        {{ create_button('delete', '<?= $route_name; ?>_delete', {'<?= $entity_identifier; ?>': <?= $entity_twig_var_singular; ?>.<?= $entity_identifier; ?>}, 'ROLE_<?= $entity_class_name_upper; ?>_DELETE') }}
                                    </td>
                                </tr>
                            {% else %}
                                <tr>
                                    <td colspan="<?= (count($entity_fields) + 1); ?>">{{ 'crud.list.no_records_found'|trans({}, 'MicayaelAdminLteMakerBundle') }}</td>
                                </tr>
                            {% endfor %}
                            </tbody>
                        </table>

                    </div>

                {% endblock %}

            {% endembed %}

        </div>
    </div>

{% endblock %}
