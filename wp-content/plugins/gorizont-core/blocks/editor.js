(function (blocks, element, components, editor, serverRender) {
  "use strict";
  const el = element.createElement;
  const Fragment = element.Fragment;
  const ServerSideRender = serverRender.default || serverRender;
  blocks.registerBlockType("gorizont/collection", {
    apiVersion: 3,
    title: "Каталог ГОРИЗОНТ",
    description: "Карточки услуг или проектов с фильтром и пагинацией.",
    icon: "grid-view",
    category: "widgets",
    attributes: {
      kind: { type: "string", default: "g_project" },
      count: { type: "number", default: 3 },
      filters: { type: "boolean", default: false },
      pagination: { type: "boolean", default: false },
    },
    supports: { align: ["wide", "full"], html: false },
    edit: function (props) {
      return el(
        Fragment,
        null,
        el(
          editor.InspectorControls,
          null,
          el(
            components.PanelBody,
            { title: "Настройки каталога" },
            el(components.SelectControl, {
              label: "Содержимое",
              value: props.attributes.kind,
              options: [
                { label: "Проекты", value: "g_project" },
                { label: "Услуги", value: "g_service" },
              ],
              onChange: (v) => props.setAttributes({ kind: v }),
            }),
            el(components.RangeControl, {
              label: "Карточек на странице",
              min: 1,
              max: 24,
              value: props.attributes.count,
              onChange: (v) => props.setAttributes({ count: v }),
            }),
            el(components.ToggleControl, {
              label: "Фильтр по категориям",
              checked: props.attributes.filters,
              onChange: (v) => props.setAttributes({ filters: v }),
            }),
            el(components.ToggleControl, {
              label: "Пагинация",
              checked: props.attributes.pagination,
              onChange: (v) => props.setAttributes({ pagination: v }),
            }),
          ),
        ),
        el(
          "div",
          editor.useBlockProps(),
          el(ServerSideRender, {
            block: "gorizont/collection",
            attributes: props.attributes,
          }),
        ),
      );
    },
    save: () => null,
  });
  blocks.registerBlockType("gorizont/contacts", {
    apiVersion: 3,
    title: "Контакты ГОРИЗОНТ",
    description: "Контакты из настроек ГОРИЗОНТ.",
    icon: "phone",
    category: "widgets",
    supports: { align: ["wide"], html: false },
    edit: () =>
      el(
        "div",
        editor.useBlockProps(),
        el(ServerSideRender, { block: "gorizont/contacts" }),
      ),
    save: () => null,
  });
  blocks.registerBlockType("gorizont/details", {
    apiVersion: 3,
    title: "Характеристики ГОРИЗОНТ",
    icon: "list-view",
    category: "widgets",
    usesContext: ["postId", "postType"],
    supports: { html: false },
    edit: () =>
      el(
        "div",
        editor.useBlockProps({ className: "g-editor-placeholder" }),
        "Характеристики текущей услуги или проекта. Значения редактируются в полях под содержимым записи.",
      ),
    save: () => null,
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.blockEditor,
  window.wp.serverSideRender,
);
