<template>
	<!-- New K5 code -->
	<section
		:data-selected="isSelected"
		class="k-layout"
		tabindex="0"
		@click="$emit('select')"
	>
		<k-grid class="k-layout-columns">
			<k-layout-column
				v-for="(column, columnIndex) in columns"
				:key="column.id"
				v-bind="{
					...column,
					endpoints,
					fieldsetGroups,
					fieldsets
				}"
				@input="
					$emit('updateColumn', {
						column,
						columnIndex,
						blocks: $event
					})
				"
			/>
		</k-grid>
		<!-- new v-if? -->
		<nav v-if="!disabled" class="k-layout-toolbar">
			<k-button
				v-if="settings"
				:title="$t('settings')"
				class="k-layout-toolbar-button"
				icon="settings"
				@click="openSettings"
			/>

			<!-- new v-if compared to native -->
			<k-button
				v-if="!layoutEditingIsDisabled"
				class="k-layout-toolbar-button"
				icon="angle-down"
				@click="$refs.options.toggle()"
			/>
			<k-dropdown-content ref="options" :options="options" align-x="end" />
			<!-- new v-if compared to native -->
			<k-sort-handle v-if="!layoutEditingIsDisabled"/>
		</nav>
	</section>
</template>

<script>
import Column from "@KirbyPanel/components/Forms/Layouts/LayoutColumn.vue";
import KLayout from "@KirbyPanel/components/Forms/Layouts/Layout.vue";
import TranslatedLayoutMixin from "~/components/TranslatedLayoutMixin.js";

/**
 * @internal
 */
export default {
	extends: KLayout,//"k-layout",
	// extends: "k-layout",
	components: {
		"k-layout-column": Column
	},
	mixins: [
		TranslatedLayoutMixin
	],
};
</script>

<style>

</style>
