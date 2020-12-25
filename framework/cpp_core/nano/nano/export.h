#pragma once

#ifndef BUILD_STATIC
# if defined(NANO_LIB)
#  define NANO_EXPORT __declspec(dllexport)
# else
#  define NANO_EXPORT __declspec(dllimport)
# endif
#else
# define NANO_EXPORT
#endif
