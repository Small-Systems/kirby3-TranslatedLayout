
export default {
    computed: {

        // Editing is only allowed in the default language
		layoutEditingIsDisabled() {
			// Note: on single lang installations, $language is null --> always allow editing layouts
			//debugger;
			if(!this.$panel.language) return false;

			// Behave as normal on 

			// Is the current language default AND are we child of translated-*-component ?
			return (!this.$panel.language.default) && this.isWithinTranslatedComponent;
			//return window.panel.$language.default;
		},

        // Helper to figure out if a component is in <k-blocks>/<k-layouts> or rather <k-translated-blocks>/<k-translated-layouts>
		isWithinTranslatedComponent(){
			let tmpParent = this; // Start with self
            const translatedComponents = ['translatedblocks', 'translatedlayout'];
			while( tmpParent != this.$root && tmpParent != null && tmpParent!=null ){
				if( tmpParent.type && translatedComponents.includes(tmpParent.type) ){
					return true;
				}
				tmpParent = tmpParent.$parent;
			}
			return false;
		},
    },
	methods: {
		// Helper for replacing native methods on mount.
		// Before: Native functions : myFunc,		Custom functions : myFuncCustom.
		// After : Native functions : myFuncNative,	Custom functions : myFuncCustom & myFunc
		// So we replace the native functions, still being able to call them.
		invertCustomAndNativeFunctions(funcNames){
			for(const fn of funcNames){
				if(true){ // Todo: make debug only ?
					if( !this[fn] ){ // original doesn't exist !
						window.console.log("Native function replacement hack: `"+fn+"` doesn't exist anymore. Please fix me.");
						continue;
					}
					if( !this[fn + 'Custom'] ){ // Target
						window.console.log("Native function replacement hack: `"+fn+"Custom` doesn't exist. Please implement it !");
						continue;
					}
				}
				if(this[fn + 'Native']) continue; // if Native is set, this has already been bound
				this[fn + 'Native'] = this[fn]; this[fn] = this[fn + 'Custom'];
			}
		}
	},
}