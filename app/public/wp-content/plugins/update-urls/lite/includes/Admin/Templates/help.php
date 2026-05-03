<div class="bg-white">
	<div class="grid grid-cols-1 gap-x-8 gap-y-10 border-b border-gray-900/10 pb-12 m-5 md:grid-cols-2">
		<div class="grid grid-col-3">
			<label class="my-5 text-xl"><strong><?php _e( 'System Info', 'better-search-replace' ); ?></strong></label>
			<textarea readonly="readonly" class="w-full h-[480px] text-white bg-black" onclick="this.focus(); this.select()" name='bsr-sysinfo'><?php echo \KaizenCoders\UpdateURLS\Tracker::get_system_info(); ?></textarea>
		</div>
	</div>
</div>