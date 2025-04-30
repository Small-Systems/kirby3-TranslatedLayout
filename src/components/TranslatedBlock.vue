<template>
	<div
		ref="container"
		:class="[
			'k-block-container',
			'k-block-container-fieldset-' + type,
			containerType ? 'k-block-container-type-' + containerType : '',
			$attrs.class
		]"
		:data-disabled="isDisabled"
		:data-hidden="isHidden"
		:data-id="id"
		:data-last-selected="isLastSelected"
		:data-selected="isSelected"
		:data-translate="fieldset.translate"
		:style="$attrs.style"
		:tabindex="isDisabled ? null : 0"
		@keydown.ctrl.j.prevent.stop="$emit('merge')"
		@keydown.ctrl.alt.down.prevent.stop="$emit('selectDown')"
		@keydown.ctrl.alt.up.prevent.stop="$emit('selectUp')"
		@keydown.ctrl.shift.down.prevent.stop="$emit('sortDown')"
		@keydown.ctrl.shift.up.prevent.stop="$emit('sortUp')"
		@keydown.ctrl.backspace.stop="backspace"
		@focus.stop="onFocus"
		@focusin.stop="onFocusIn"
	>
		<div :class="className" :data-disabled="isDisabled" class="k-block">
			<component
				:is="customComponent"
				ref="editor"
				v-bind="$props"
				:tabs="tabs"
				v-on="listeners"
			/>
		</div>

		<!-- new element compared to native template -->
		<k-dropdown class="k-toolbar k-block-options" v-if="layoutEditingIsDisabled && isEditable">
			<k-button
				v-if="isEditable"
				:tooltip="$t('edit')"
				icon="edit"
				class="k-block-options-button"
				@click="listenersForOptions.open()"
			/>
		</k-dropdown>
		<!-- new v-else-if compared to native template -->
		<k-block-options
			v-else-if="!isDisabled"
			ref="options"
			v-bind="{
				isBatched,
				isEditable,
				isFull,
				isHidden,
				isMergable,
				isSplitable: isSplitable()
			}"
			v-on="listenersForOptions"
		/>

		<!-- <k-form-drawer
			v-if="isEditable && !isBatched"
			:id="id"
			ref="drawer"
			:icon="fieldset.icon || 'box'"
			:tabs="tabs"
			:title="fieldset.name"
			:value="content"
			class="k-block-drawer"
			@close="focus()"
			@input="$emit('update', $event)"
		>
			<template #options>
				<k-button
					v-if="isHidden"
					class="k-drawer-option"
					icon="hidden"
					@click="$emit('show')"
				/>
				<k-button
					:disabled="!prev"
					class="k-drawer-option"
					icon="angle-left"
					@click.prevent.stop="goTo(prev)"
				/>
				<k-button
					:disabled="!next"
					class="k-drawer-option"
					icon="angle-right"
					@click.prevent.stop="goTo(next)"
				/>
				<k-button
					class="k-drawer-option"
					icon="trash"
					@click.prevent.stop="confirmToRemove"
				/>
			</template>
		</k-form-drawer>

		<k-remove-dialog
			ref="removeDialog"
			:text="$t('field.blocks.delete.confirm')"
			@submit="remove"
		/> -->
	</div>
</template>

<script>

import TranslatedLayoutMixin from "~/components/TranslatedLayoutMixin.js";

// Compared to the native component :
// - Options menu is hidden in translations. Note: if this is the only change needed, could be done via css + over-riding the k-block-options component for making that easier.

export default {
	extends: 'k-block',
	mixins: [
        TranslatedLayoutMixin,
    ],
	props: {
		_devInfo: {
			// Vue-Dev-info, to clarify that this is not the original, for devs debugging with the inspector.
			type: String,
			default: "Warning: I'm not the default k-block. I have been replaced by a k-translated-block !",
		},
	}
};
</script>

<style>

</style>
