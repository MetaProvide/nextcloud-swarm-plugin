# SPDX-FileCopyrightText: Copyright (c) 2022, MetaProvide Holding EKF
# SPDX-License-Identifier: AGPL-3.0-or-later

{
	inputs.nixpkgs.url = github:nixos/nixpkgs/nixos-22.05;
	outputs = { nixpkgs, ... }:
	{ devShells.x86_64-linux.default = (import nixpkgs { system = "x86_64-linux"; }).callPackage ./shell.nix {};
	};
}
