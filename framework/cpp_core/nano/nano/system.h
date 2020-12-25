#pragma once
#include "export.h"

namespace nano
{
	int NANO_EXPORT run(int argc, char** argv);
}

#define DEFINE_NANO_MAIN \
int main(int argc, char** argv) { return nano::run(argc, argv); }