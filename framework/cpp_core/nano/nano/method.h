#pragma once
#include "parameter.h"
#include <functional>
#include <vector>
#include <QJsonObject>
#include <QStringList>

namespace nano
{
	class service;
	struct method
	{
		using func_ptr_type = std::function<QJsonObject(service*, QStringList)>;
		QString name;
		std::vector<parameter> parameters;
		func_ptr_type func_ptr;
		service* service;

		QJsonObject operator()(QStringList list) const
		{
			return func_ptr(service, std::move(list));
		}
	};
}