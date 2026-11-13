<?php
/** Escaped WordPress management screens. No storage writes. @license GPL-3.0-or-later */
namespace Papijo\H5PThemerWordPress\Admin;
use Papijo\H5PThemerWordPress\Storage\Schema;
use Papijo\H5PThemerWordPress\Theme\Color_Properties;
use Papijo\H5PThemerWordPress\Theme\Preset_Catalog;
use Papijo\H5PThemerWordPress\Theme\Theme_Operations;
use Papijo\H5PThemerWordPress\Theme\Palette_Resolver;
use Papijo\H5PThemerWordPress\Publication\Publication_Policy;

final class Admin_View {
	private $url;
	private $preview;
	private $posted = array();
	private $errors = array();
	public function __construct( $url, ?Theme_Preview $preview = null ) { $this->url = $url; $this->preview = $preview ?: new Theme_Preview(); }
	private function t( $text ) { return esc_html( __( $text, 'papijo-h5p-themer-wordpress' ) ); }
	public function link( $view = 'overview', $source = '' ) { return add_query_arg( array('pjh5pwp_view'=>$view,'pjh5pwp_source'=>$source), $this->url ); }
	public function render( array $status, $view = 'overview', $source = '', array $posted = array(), array $errors = array(), $saved = false ) {
		if ( ! Admin_Service::allowed() ) { return '<p role="alert">' . esc_html( Admin_Messages::text('forbidden') ) . '</p>'; }
		$this->posted = $posted; $this->errors = $errors;
		$html = '<div class="wrap pjh5pwp-admin"><h1>' . $this->t('Papi Jo H5P Themes') . '</h1><p>' . $this->t('Manage H5P color themes, publication settings, and the global default theme. Learners can configure their Theme and Density preferences in their WordPress profiles when enabled.') . '</p>';
		foreach ( array_unique( array_merge( $errors, $status['errors'] ) ) as $error ) {
			$html .= '<div class="notice notice-error" role="alert"><p>' . esc_html( Admin_Messages::text( $error ) ) . ' <code>' . esc_html( $error ) . '</code></p></div>';
		}
		if ( $saved ) { $html .= '<div class="notice notice-success"><p>' . ('edit' === $view ? $this->t('Theme saved successfully.') : $this->t('Theme library saved.')) . '</p></div>'; }
		if ( 'absent' === $status['state'] ) {
			$html .= '<p>' . esc_html( Admin_Messages::text('storage_absent') ) . '</p>' . $this->start( 'initialize', '', null ) . $this->button('Initialize theme storage') . '</form><h2>' . $this->t('Immutable built-in themes') . '</h2><ul>';
			foreach ( (new Preset_Catalog())->all()['data'] as $id=>$preset ) { $html .= '<li>' . esc_html($preset['label']) . '</li>'; }
			return $html . '</ul></div>';
		}
		if ( 'ready' !== $status['state'] ) { return $html . $this->retained_values() . '</div>'; }
		$state = $status['data'];
		if ( ! in_array($view,array('create','edit','duplicate'),true) ) {
			$html .= '<p><a href="' . esc_url($this->url) . '">' . $this->t('Theme overview') . '</a> · <a href="' . esc_url($this->link('create')) . '">' . $this->t('Add custom theme') . '</a> · <a href="' . esc_url($this->link('import')) . '">' . $this->t('Import theme colors') . '</a></p>';
		}
		if ( 'overview' === $view ) { return $html . $this->overview($state) . '</div>'; }
		$reference = Admin_Service::reference($source);
		if ( 'create' !== $view && ( false === $reference || (new Palette_Resolver())->resolve($reference,$state['definitions'])['errors'] ) ) { return $html . '<p role="alert">' . $this->t('The requested theme is unavailable. No fallback was selected.') . '</p>' . $this->retained_values() . '</div>'; }
		if ( 'view' === $view ) {
			$theme = (new Palette_Resolver())->resolve($reference,$state['definitions'])['data'];
			$duplicate = 'builtin' === $theme['kind'] ? '<p><a class="button" href="' . esc_url($this->link('duplicate',$source)) . '">' . $this->t('Duplicate theme') . '</a></p>' : '';
			return $html . '<h2>' . esc_html($theme['label']) . '</h2><p>' . $this->t('Built-in themes are immutable. Duplicate a theme to customize it. H5P Core defaults are distinct from Daylight.') . '</p>' . $duplicate . $this->palette($theme['colors']) . $this->preview->render($reference,$state['definitions']) . '</div>';
		}
		if ( 'delete' === $view ) {
			$blocked = (new Publication_Policy())->deletion_errors($reference,$state['definitions'],array($state['settings']['default_theme']));
			if ($blocked) { return $html . '<p role="alert">' . esc_html(Admin_Messages::text($blocked[0])) . '</p></div>'; }
			return $html . '<h2>' . $this->t('Delete custom theme') . ': ' . esc_html($state['definitions'][$reference['id']]['label']) . '</h2><p>' . $this->t('This removes the custom definition. References to the deleted theme will use the domain fallback policy. This action cannot be undone.') . '</p>' . $this->start('delete',$source,$state) . '<p><label><input type="checkbox" name="confirm" value="1"> ' . $this->t('I confirm deletion of this custom theme.') . '</label></p>' . $this->button('Delete theme') . '</form></div>';
		}
		if ( ! in_array($view,array('create','edit','duplicate'),true) ) { return $html . '<p role="alert">' . $this->t('Unknown administration page.') . '</p></div>'; }
		return $html . $this->editor($state,$view,$source,$reference) . '</div>';
	}
	private function overview( array $state ) {
		$catalog = Admin_Service::catalog($state);
		$html = '<h2>' . $this->t('Global default') . '</h2>' . $this->start('default','',$state) . '<p><label for="pjh5pwp-default">' . $this->t('Default theme') . '</label> <select id="pjh5pwp-default" name="default_theme">';
		$chosen = null === $state['settings']['default_theme'] ? 'core' : $state['settings']['default_theme']['kind'] . ':' . $state['settings']['default_theme']['id'];
		if ($this->is_post('default','')) { $chosen = $this->value('default_theme',$chosen,100); }
		foreach ($catalog as $token=>$entry) { $html .= '<option value="' . esc_attr($token) . '"' . ($chosen===$token?' selected':'') . '>' . esc_html($entry['label']) . '</option>'; }
		$html .= '</select></p><p>' . $this->t('Any valid theme can be the global default, including an unpublished custom theme. H5P Core defaults preserve original colors. Density is an independent profile preference.') . '</p>' . $this->button('Save global default') . '</form><h2>' . $this->t('Theme catalog and learner visibility') . '</h2><p>' . $this->t('Site default, H5P Core defaults and Daylight are always available. New custom themes remain unpublished until explicitly enabled here. All five built-ins remain available in profiles; custom themes require publication.') . '</p>' . $this->start('visibility','',$state);
		foreach (array('builtin'=>'Built-in themes','custom'=>'Custom themes') as $kind=>$heading) {
			$html .= '<h3>' . $this->t($heading) . '</h3><table class="widefat striped"><thead><tr><th>' . $this->t('Theme / identifier') . '</th><th>' . $this->t('Global default') . '</th><th>' . $this->t('Learner-visible') . '</th><th>' . $this->t('Operations') . '</th></tr></thead><tbody>';
			$rows=0;
			foreach ($catalog as $token=>$entry) {
				if ( ('builtin'===$kind && 0!==strpos($token,'builtin:') && 'core'!==$token) || ('custom'===$kind && 0!==strpos($token,'custom:')) ) { continue; }
				++$rows;
				$html .= '<tr><td>' . esc_html($entry['label']) . '<br><code>' . esc_html($token) . '</code></td><td>' . ($entry['reference']===$state['settings']['default_theme']?$this->t('Selected'):'—') . '</td><td>';
				if (in_array($token,Publication_Policy::fixed_tokens(),true)) { $html.=$this->t('Always available'); }
				else {
					$checked = !empty($state['publication']['themes'][$token]);
					if ($this->is_post('visibility','')) { $checked=isset($this->posted['visible']) && is_array($this->posted['visible']) && isset($this->posted['visible'][$token]) && '1'===$this->posted['visible'][$token]; }
					$html.='<label><input type="checkbox" name="visible[' . esc_attr($token) . ']" value="1"' . ($checked?' checked':'') . '> <span class="screen-reader-text">' . esc_html($entry['label']) . ': </span>' . $this->t('Published') . '</label>';
				}
				$html .= '</td><td>'; $operations='custom'===$kind?array('edit'=>'Edit / rename','duplicate'=>'Duplicate','delete'=>'Delete'):array('view'=>'View','duplicate'=>'Duplicate');
				if ('core'===$token) { $operations=array('view'=>'View'); }
				if ('custom'===$kind && $entry['reference']===$state['settings']['default_theme']) { unset($operations['delete']); }
				$links=array(); foreach ($operations as $view=>$label) { $links[]='<a href="' . esc_url($this->link($view,$token)) . '">' . $this->t($label) . '</a>'; }
				if ('custom'===$kind) { $links[]=ltrim((new Transfer_View($this->url))->export_button($entry['reference']['id'])); }
				$html .= implode(' · ',$links) . '</td></tr>';
			}
			if (!$rows) { $html.='<tr><td colspan="4">' . $this->t('No custom themes.') . '</td></tr>'; }
			$html.='</tbody></table>';
		}
		return $html . '<p>' . $this->button('Save learner visibility') . '</p></form>' . (new Transfer_View($this->url))->exports($state);
	}
	private function editor( array $state, $op, $source, $reference ) {
		$ops=new Theme_Operations(); $builtin_copy='duplicate'===$op && is_array($reference) && 'builtin'===$reference['kind'];
		if ('edit'===$op && (!is_array($reference) || 'custom'!==$reference['kind'])) { return '<p role="alert">' . esc_html(Admin_Messages::text('theme_not_mutable')) . '</p>'; }
		if ('create'===$op) {
			$seed_id='pjh5pwp_seed'; while(isset($state['definitions'][$seed_id])) { $seed_id.='_x'; if(strlen($seed_id)>60){return '<p>' . $this->t('Cannot prepare a starting theme.') . '</p>'; } }
			$result=$ops->create_from_default($state['settings']['default_theme'],$seed_id,'New theme',$state['definitions']);
		} elseif ('duplicate'===$op) {
			$seed_id='pjh5pwp_copy'; while(isset($state['definitions'][$seed_id])) { $seed_id.='_x'; if(strlen($seed_id)>60){return '<p>' . $this->t('Cannot prepare a theme copy.') . '</p>'; } }
			$result=$ops->duplicate($reference,$seed_id,'Theme copy',$state['definitions']);
		} else { $result=array('data'=>$state['definitions'][$reference['id']],'errors'=>array()); }
		if ($result['errors']) { return '<p role="alert">' . esc_html(Admin_Messages::text($result['errors'][0])) . '</p>'; }
		$definition=$result['data']; $inherited=array();
		if (isset($definition['base_preset'])) { $base=$definition['base_preset']; $inherited=(new Preset_Catalog())->get($base['id'],$base['version'])['data']['colors']; }
		$display = 'create'===$op ? array_replace($inherited,$definition['colors']) : $inherited;
		$default_id='edit'===$op?$definition['id']:'';
		$default_label='edit'===$op?$definition['label']:('duplicate'===$op?(new Palette_Resolver())->resolve($reference,$state['definitions'])['data']['label'] . ' copy':'');
		$values=array('id'=>$default_id,'label'=>$default_label,'description'=>isset($definition['description'])?$definition['description']:'');
		if($this->is_post($op,$source)) { foreach($values as $key=>$value) { $values[$key]=$this->value($key,$value,'description'===$key?4096:255); } }
		$html='<h2>' . $this->t('create'===$op?'Add custom theme':('duplicate'===$op?'Duplicate theme':'Edit custom theme')) . '</h2>' . $this->start($op,$source,$state) . '<details open><summary>' . $this->t('General') . '</summary>';
		foreach(array('id'=>'Identifier','label'=>'Name') as $key=>$label) {
			$html.='<p><label for="pjh5pwp-' . esc_attr($key) . '">' . $this->t($label) . '</label><br><input class="regular-text" id="pjh5pwp-' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($values[$key]) . '" required maxlength="' . ('id'===$key?'64':'255') . '"' . ('id'===$key && 'edit'===$op?' readonly':'') . '></p>';
		}
		$html.='<p>' . $this->t('Identifiers are permanent: lowercase letters, digits and underscores, starting with a letter (maximum 64 characters). Rename a theme by changing its Name.') . '</p><p><label for="pjh5pwp-description">' . $this->t('Description') . '</label><br><textarea class="large-text" rows="3" id="pjh5pwp-description" name="description" maxlength="4096">' . esc_html($values['description']) . '</textarea></p><p>' . $this->t('Pinned inheritance') . ': <code>' . esc_html(isset($definition['base_preset'])?$base['id'] . ' @ ' . $base['version']:'H5P Core defaults (legacy definition)') . '</code></p></details>';
		if('create'===$op) { $html.='<p>' . $this->t('Starting colors were captured from the global default. Core defaults use Daylight as the starting preset. Blank fields keep the starting colors; after saving, blank fields restore pinned inheritance. New themes start unpublished.') . '</p>'; }
		else { $html.='<p>' . $this->t('Blank color fields inherit from the pinned preset (or H5P Core defaults for a legacy theme). Enter only #RGB or #RRGGBB. Density is independent of this palette.') . '</p>'; }
		if($builtin_copy) { $html.='<p>' . $this->t('This built-in palette is read-only. Save the unpublished copy, then edit its overrides.') . '</p>' . $this->palette($inherited); }
		else {
			foreach(Color_Properties::groups() as $group=>$meta) {
				$group_html=''; $invalid=false;
				foreach($meta['fields'] as $suffix=>$label) {
					$property='--h5p-theme-' . $suffix; $value='create'===$op?'':(isset($definition['colors'][$property])?$definition['colors'][$property]:'');
					if($this->is_post($op,$source) && isset($this->posted['colors']) && is_array($this->posted['colors']) && isset($this->posted['colors'][$property]) && is_string($this->posted['colors'][$property]) && strlen($this->posted['colors'][$property])<=128) { $value=$this->posted['colors'][$property]; }
					$bad=''!==$value && !Color_Properties::valid_hex($value); $invalid=$invalid||$bad;
					$base_value=isset($display[$property])?$display[$property]:'';
					$group_html.='<div class="pjh5pwp-color-row"><label for="pjh5pwp-' . esc_attr($suffix) . '">' . $this->t($label) . '</label><div><input type="text" id="pjh5pwp-' . esc_attr($suffix) . '" name="colors[' . esc_attr($property) . ']" value="' . esc_attr($value) . '" maxlength="128" data-pjh5pwp-color="' . esc_attr($property) . '" data-pjh5pwp-inherited="' . esc_attr($base_value) . '" aria-describedby="pjh5pwp-help-' . esc_attr($suffix) . '"' . ($bad?' aria-invalid="true"':'') . '><input type="color" class="pjh5pwp-picker" aria-label="' . esc_attr(__($label,'papijo-h5p-themer-wordpress')) . '" hidden><small id="pjh5pwp-help-' . esc_attr($suffix) . '"><code>' . esc_html($property) . '</code><br>' . $this->t('Inherited / starting value') . ': <code>' . esc_html(''!==$base_value?$base_value:'H5P Core defaults') . '</code> <span class="pjh5pwp-color-status" role="status">' . ($bad?$this->t('Invalid hexadecimal color.'):'') . '</span></small></div></div>';
				}
				$html.='<details class="pjh5pwp-color-group"' . ($invalid?' open':'') . '><summary>' . $this->t($meta['title']) . '</summary>' . $group_html . '</details>';
			}
		}
		$html.='<p>' . $this->button('Save theme') . ' <a class="button" href="' . esc_url($this->url) . '">' . $this->t('Back to theme overview') . '</a> <button type="reset" class="button">' . $this->t('Reset unsaved changes') . '</button></p>';
		if('edit'===$op) { $html.=$this->preview->render($reference,$state['definitions'],true); }
		return $html . '</form>';
	}
	private function palette( array $colors ) {
		$html='<table class="widefat striped pjh5pwp-palette"><thead><tr><th>' . $this->t('Property') . '</th><th>' . $this->t('Resolved value (read-only)') . '</th></tr></thead><tbody>';
		foreach($colors as $property=>$value) { $html.='<tr><td><code>' . esc_html($property) . '</code></td><td><code>' . esc_html($value) . '</code></td></tr>'; }
		return $html . '</tbody></table>';
	}
	private function is_post( $op, $source ) { return isset($this->posted['pjh5pwp_op'],$this->posted['source']) && $op===$this->posted['pjh5pwp_op'] && $source===$this->posted['source']; }
	/** A deleted source or broken backend must not discard safe text the administrator can copy. */
	private function retained_values() {
		if ( ! $this->errors || ! $this->posted ) { return ''; }
		$safe = array();
		foreach ( array( 'id' => 64, 'label' => 255, 'description' => 4096 ) as $key => $limit ) {
			if ( isset($this->posted[$key]) && is_string($this->posted[$key]) && strlen($this->posted[$key]) <= $limit && 1 === preg_match('//u',$this->posted[$key]) ) { $safe[$key] = $this->posted[$key]; }
		}
		if ( isset($this->posted['colors']) && is_array($this->posted['colors']) ) {
			foreach ( Color_Properties::supported() as $property ) {
				if ( isset($this->posted['colors'][$property]) && is_string($this->posted['colors'][$property]) && strlen($this->posted['colors'][$property]) <= 128 && 1 === preg_match('//u',$this->posted['colors'][$property]) ) { $safe['colors'][$property] = $this->posted['colors'][$property]; }
			}
		}
		return $safe ? '<p><label for="pjh5pwp-retained">' . $this->t('Submitted values (not saved). Copy these before reloading.') . '</label><textarea id="pjh5pwp-retained" class="large-text" rows="10" readonly>' . esc_html(wp_json_encode($safe,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) . '</textarea></p>' : '';
	}
	private function value( $key, $fallback, $max ) { return isset($this->posted[$key]) && is_string($this->posted[$key]) && strlen($this->posted[$key])<=$max && 1===preg_match('//u',$this->posted[$key]) ? $this->posted[$key] : $fallback; }
	private function start( $op, $source, $state ) {
		$revision=null===$state?'absent':(string)$state['revision']; $context=Admin_Service::context($state);
		$fields=array('pjh5pwp_op'=>$op,'source'=>$source,'revision'=>$revision,'pjh5pwp_context'=>$context,'pjh5pwp_nonce'=>wp_create_nonce(Admin_Service::nonce_action($op,$source,$revision,$context)));
		// Preserve the original binding after errors. Never silently rebase submitted values.
		if($this->is_post($op,$source)) { foreach(array('revision','pjh5pwp_context','pjh5pwp_nonce') as $key) { $fields[$key]=$this->value($key,'',256); } }
		$html='<form method="post" class="pjh5pwp-form" action="' . esc_url($this->link(in_array($op,array('initialize','default','visibility'),true)?'overview':$op,$source)) . '">';
		foreach($fields as $key=>$value) { $html.='<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">'; }
		return $html;
	}
	private function button( $label ) {
		$blocked=array_intersect($this->errors,array('storage_conflict','storage_write_unknown','storage_cache_failed','invalid_nonce','malformed_submission'));
		return '<button type="submit" class="button button-primary"' . ($blocked?' disabled':'') . '>' . $this->t($label) . '</button>';
	}
}
