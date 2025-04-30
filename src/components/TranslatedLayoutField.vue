<template>
  <!-- Changes : Class items -->
  <k-field
    v-bind="$props"
    :class="{
      'k-translated-layout-field': true,
      'k-layout-field': true,
      'layouts-disabled': layoutEditingIsDisabled
    }"
    :style="$attrs.style"
  >
    <!-- New K5 LayoutField -->
    <template v-if="!disabled && hasFieldsets" #options>
      <!-- new v-if ! -->
			<k-button-group v-if="!layoutEditingIsDisabled" layout="collapsed">
				<k-button
					:autofocus="autofocus"
					:text="$t('add')"
					icon="add"
					variant="filled"
					size="xs"
					class="input-focus"
					@click="$refs.layouts.select(0)"
				/>
				<k-button
					icon="dots"
					variant="filled"
					size="xs"
					@click="$refs.options.toggle()"
				/>
				<k-dropdown-content ref="options" :options="options" align-x="end" />
			</k-button-group>
		</template>

		<k-input-validator
			v-bind="{ min, max, required }"
			:value="JSON.stringify(value)"
		>
      <!-- Changes: rename `k-layouts` to `k-translated-layouts`, added class -->
			<k-translated-layouts
				ref="layouts"
				v-bind="$props"
				@input="$emit('input', $event)"
			/>
		</k-input-validator>
    <!-- Changes: v-if -->
		<footer v-if="!disabled && hasFieldsets && !layoutEditingIsDisabled">
			<k-button
				:title="$t('add')"
				icon="add"
				size="xs"
				variant="filled"
				@click="$refs.layouts.select(value.length)"
			/>
		</footer>
  </k-field>
</template>

<script>
// This component is a duplicate of components/layouter/Layouts.vue (changes are commented in the template, in case it breaks).
// Purpose: Disable some editing functions but not all, as it is the case with props.disabled

// Notes:
// - BlockLayouts.disabled -> disables layout sidebar but still maintains blocks editable, but no layout settings if they need translations
// - KLayout.disabled -> disables a single row
// - So we need to replace 3 templates just to introduce a new prop, and keep them up to date

// K3
// import TranslatedBlockLayouts from "~/components/TranslatedLayouterLayouts.vue";
// import TranslatedLayoutMixin  from "~/components/TranslatedLayoutMixin.js";

// K5
// Changes:
// - components/Layouter/Layouts.vue -> components/Forms/Layouts/Layouts.vue
// - components/Layouter/Layout.vue  -> components/Forms/Layouts/Layout.vue
// - Now this is based on components/Forms/Fields/LayoutField.vue (instead of Layouts.vue)
// import { props as LayoutsProps } from "@/components/Forms/Layouts/Layouts.vue";
// import TranslatedLayouts from "@KirbyPanel/Forms/Layouts/Layouts.vue";
import TranslatedLayouts from "~/components/TranslatedLayouts.vue";
import TranslatedLayoutMixin  from "~/components/TranslatedLayoutMixin.js";

export default {
    extends: 'k-layout-field',
    components: {
        'k-translated-layouts' : TranslatedLayouts,
    },
    mixins: [
        TranslatedLayoutMixin,
    ],
    // computed : {
    //   options(){
    //     return [];
    //   },
    // }
};
</script>

<style lang="scss">

// Override Layout css because it's not meant to be disabled
.k-translated-layout-field {

  &.layouts-disabled {
    // .k-layout {
    //   padding: 0; // removes toolbar width on both 
    // }

    // .k-block-options {
    //   button.k-block-options-button {
    //     display: none; // hide all by default
    //     $editLangs: Edit, Éditer, Bearbeiten, Wijzig; // Todo: more langs need to be in here... the exact kirby translations or 'edit'
        
    //     @each $translation in $editLangs {
    //       &[title="#{$translation}"] { // Only option to show... but not multilingual ! !
    //         display: inherit;
    //       }
    //     }

    //     &:has(.k-icon-edit){ // CSS4 not supported by most browsers... but language-universal
    //       display: none;
    //     }
    //   }
    // }
  }
}
</style>
