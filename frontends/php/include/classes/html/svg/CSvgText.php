<?php
/*
** Zabbix
** Copyright (C) 2001-2018 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


class CSvgText extends CTag {

	public function __construct($x, $y, $text, $color) {
		parent::__construct('text', true);

		// should define x, y
		$this->x = $x;
		$this->y = $y;

		/**
		 * TODO
		 * consider to create new CSvgTag class to extend and move svg tag specific attributes liek x,y there.
		 *
		 * consider set x and y right before stringify, e.g., define tostring function and add attributes right before
		 * call parent::tostring. This allows change x & y more flexible.
		 */
		$this->setAttribute('font-family', 'Arial, Tahoma, Verdana, sans-serif');
		$this->setAttribute('x', $this->x);
		$this->setAttribute('y', $this->y);
		$this->setAttribute('fill', $color);
		$this->setFontSize(13);
		$this->addItem($text);

		return $this;
	}

	public function setAngle($angle) {
		$this->setAttribute('transform', 'rotate('.$angle.','.$this->x.','.$this->y.')');

		return $this;
	}

	public function setFontSize($font_size) {
		$this->setAttribute('font-size', $font_size);

		return $this;
	}
}
