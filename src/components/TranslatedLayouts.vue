<template>
	<!-- New k5 code (todo: port isLayoutEditingDisabled()) -->
	<div>
		<template v-if="hasFieldsets && rows.length">
			<!-- Added class -->
			<!-- todo: disable drag ! -->
			<k-draggable v-bind="draggableOptions" class="k-layouts k-translated-layouts" @sort="save">
				<!-- Replaced `k-layout` by `k-translated-layout` -->
				<k-translated-layout
					v-for="(layout, index) in rows"
					:key="layout.id"
					v-bind="{
						...layout,
						disabled,
						endpoints,
						fieldsetGroups,
						fieldsets,
						isSelected: selected === layout.id,
						layouts,
						settings,
						
					}"
					@append="select(index + 1)"
					@change="change(index, layout)"
					@copy="copy($event, index)"
					@duplicate="duplicate(index, layout)"
					@paste="pasteboard(index + 1)"
					@prepend="select(index)"
					@remove="remove(layout)"
					@select="selected = layout.id"
					@updateAttrs="updateAttrs(index, $event)"
					@updateColumn="updateColumn({ layout, index, ...$event })"
				/>
			</k-draggable>
		</template>

		<!-- Added class name -->
		<k-empty
			v-else-if="hasFieldsets === false"
			icon="dashboard"
			class="k-layout-empty k-translated-layout-empty"
		>
			{{ $t("field.blocks.fieldsets.empty") }}
		</k-empty>

		<!-- Added class name & v-else-if -->
		<k-empty v-else-if="layoutEditingIsDisabled" icon="dashboard" class="k-layout-empty k-translated-layout-empty" @click="select(0)">
			{{ empty ?? $t("field.layout.empty") }}
		</k-empty>
	</div>
</template>

<script>
// This is a minimal copy of components/Layouter/Layouts.vue which is loaded as k-block-layouts

// The purpose of the clone is to disable layouts visually in translations while maintaining blocks editable
// (if layoutsfield.props.disabled=true, blocks are also disabled)
// Changes from the original template code are commented, to be updated when kirby updates the template code.

// Old k3 methods below
// import TranslatedBlockLayout from "~/components/TranslatedLayouterLayout.vue";
// // import KBlockLayouts from "@KirbyPanel/components/Layouter/Layouts.vue"; // K3
// import KBlockLayouts from "@KirbyPanel/components/Forms/Layouts/Layout.vue"; // K 4-5. Needs hack to load `@/mixins/props.js` within: `ln -s "../../../../kirby/panel/src/mixins" ./src/mixins`
// import TranslatedLayoutMixin from "~/components/TranslatedLayoutMixin.js";

// K5 way :
import TranslatedLayout from "~/components/TranslatedLayout.vue";
// import KBlockLayouts from "@KirbyPanel/components/Layouter/Layouts.vue"; // K3
import KLayouts from "@KirbyPanel/components/Forms/Layouts/Layouts.vue"; // K 4-5. Needs hack to load `@/mixins/props.js` within: `ln -s "../../../../kirby/panel/src/mixins" ./src/mixins`
import TranslatedLayoutMixin from "~/components/TranslatedLayoutMixin.js";

/**
 * @internal
 */
export default {
	// K3
	extends: KLayouts, // Note: component is not registered globally, we have to import it separately
	// K5
	// extends: "k-layouts", // Note: component is not registered globally, we have to import it separately
	// extends: window.Vue.options.components['k-layouts'], // Note: component is not registered globally, we have to import it separately
	//inheritAttrs: true,
	components: {
		"k-translated-layout" : TranslatedLayout
	},
	mixins: [
		TranslatedLayoutMixin,
	],
	mounted: function(){
		// Manually inject functions from parent ! (we need to override them and still being able to call them)
		// this.onAddNative 			= KLayouts.methods.onAdd;
		// this.removeNative 		= KLayouts.methods.remove;
		// this.duplicateNative	= KLayouts.methods.duplicate;
		// this.selectNative 		= KLayouts.methods.select;
		// this.changeNative 		= KLayouts.methods.change;
		// this.copyNative 		= KLayouts.methods.copy;
		// older k5
		// this.addNative 			= KLayouts.methods.onAdd;
		// this.removeNative 		= KLayouts.methods.remove;
		// this.duplicateNative	= KLayouts.methods.duplicate;
		// this.selectNative 		= KLayouts.methods.select;
		// this.changeNative 		= KLayouts.methods.change;
		// this.copyNative 		= KLayouts.methods.copy;

		// Helper k5:
		// Invert functions so ours are called
		// Note : Important to do this on mounted(), beforeCreate and created() both seem too early, some aren't correctly replaced.
		// this.chooseNative = this.choose; this.choose = this.chooseCustom;
		this.invertCustomAndNativeFunctions([
			'onAdd',
			'remove',
			'select',
			'change',
			'copy',
			// 'pasteboard',
			// 'append',
			// 'remove',
			// 'removeAll',
			// 'convert',
			// 'move',
			// 'copyAll',
			'duplicate',
			// 'chooseToConvert',
			// 'add',
			// 'removeAll',
			// 'removeSelected',
		]);
	},
	// Cancel some native methods that we don't need ?
	methods: {
		// Native methods : replacements
		async onAddCustom(columns) {
			return this.layoutEditingIsDisabled ? null : this.onAddNative(columns);
		},
		duplicateCustom(index, layout) {
			return this.layoutEditingIsDisabled ? null : this.duplicateNative(index, layout);
		},
		removeCustom(layout) {
			return this.layoutEditingIsDisabled ? null : this.removeNative(layout);
		},
		selectCustom(index) {
			return this.layoutEditingIsDisabled ? null : this.selectNative(index);
		},
		changeCustom(rowIndex, layout) {
			return this.layoutEditingIsDisabled ? null : this.changeNative(rowIndex, layout);
		},
		copyCustom(e, index){
			return this.layoutEditingIsDisabled ? null : this.copyNative(e, index);
		},
		// todo: 
		// async onChange(columns, layoutIndex, payload){},
		// async paste(e, index = this.rows.length){},
		// pasteboard(index){},
		// removeAll(){},
		// save(){

		// },
		// select(index){

		// },
		// updateAttrs(layoutIndex, attrs){

		// },
		// updateColumn(args){

		// },
		// updateIds(copy){

		// },
	},

};
</script>

<style>

</style>
